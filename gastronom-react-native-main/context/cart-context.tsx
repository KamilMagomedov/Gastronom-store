import { useAuth } from '@/context/auth-context';
import { ApiCart, ApiService } from '@/services/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { Alert } from 'react-native';

const SESSION_ID_KEY = 'cart_session_id';
const QUANTITIES_KEY = 'cart_local_quantities';

type Quantities = Record<number, number>;

interface CartContextType {
  cart: ApiCart | null;
  loading: boolean;
  initialized: boolean;
  quantities: Quantities;
  getQuantity: (productId: number) => number;
  addToCart: (productId: number, quantity?: number) => Promise<void>;
  removeFromCart: (productId: number) => Promise<void>;
  updateQuantity: (productId: number, quantity: number) => Promise<void>;
  clearCart: () => Promise<void>;
  refresh: () => Promise<void>;
}

const CartContext = createContext<CartContextType>({
  cart: null,
  loading: false,
  initialized: false,
  quantities: {},
  getQuantity: () => 0,
  addToCart: async () => {},
  removeFromCart: async () => {},
  updateQuantity: async () => {},
  clearCart: async () => {},
  refresh: async () => {},
});

function cleanCartData(rawResult: any): ApiCart {
  if (!rawResult) {
    return { total_amount: '0.00', total_items: 0, expires_at: '', session_id: '', items: [] };
  }

  if (rawResult.data) {
    if (rawResult.data.data) {
      return rawResult.data.data;
    }
    return rawResult.data;
  }

  return rawResult;
}

function cartToQuantities(cart: ApiCart): Quantities {
  const result: Quantities = {};

  const cleanCart = cleanCartData(cart);

  if (!cleanCart || !Array.isArray(cleanCart.items)) {
    console.log('items не найдены или не являются массивом в очищенной корзине');
    return result;
  }

  for (const item of cleanCart.items) {
    if (item && item.product) {
      result[item.product.id] = item.quantity;
    }
  }

  return result;
}

export function CartProvider({ children }: { children: React.ReactNode }) {
  const { user, isLoading: authLoading } = useAuth();
  const [cart, setCart] = useState<ApiCart | null>(null);
  const [quantities, setQuantities] = useState<Quantities>({});
  const [loading, setLoading] = useState(false);
  const [initialized, setInitialized] = useState(false);
  const sessionIdRef = useRef<string | undefined>(undefined);
  const cartVersionRef = useRef(0);

  const quantitiesRef = useRef<Quantities>({});
  const cartMutationQueueRef = useRef<Promise<void>>(Promise.resolve());
  const cartMutationVersionRef = useRef(0);

  useEffect(() => {
    AsyncStorage.getItem(QUANTITIES_KEY).then((stored) => {
      if (stored) {
        try {
          const parsed = JSON.parse(stored) as Quantities;

          quantitiesRef.current = parsed;
          setQuantities(parsed);
        } catch {}
      }
    });
  }, []);

  const persistQuantities = (qty: Quantities) => {
    AsyncStorage.setItem(QUANTITIES_KEY, JSON.stringify(qty)).catch(() => {});
  };

  const applyQuantities = (qty: Quantities) => {
    quantitiesRef.current = qty;
    setQuantities(qty);
    persistQuantities(qty);
  };

  const getSessionId = async (): Promise<string | undefined> => {
    if (sessionIdRef.current) return sessionIdRef.current;
    const stored = await AsyncStorage.getItem(SESSION_ID_KEY);
    if (stored) {
      sessionIdRef.current = stored;
      return stored;
    }
    return undefined;
  };

  const saveSessionId = async (id: string) => {
    sessionIdRef.current = id;
    await AsyncStorage.setItem(SESSION_ID_KEY, id);
  };

  const fetchCart = useCallback(async () => {
    if (authLoading) return;
    setLoading(true);
    const version = ++cartVersionRef.current;
    try {
      const sessionId = await getSessionId();
      const result = await ApiService.getCart(user?.token, sessionId);
      if (cartVersionRef.current !== version) return;

      const cleanCart = cleanCartData(result);
      setCart(cleanCart);
      if (cleanCart.session_id) await saveSessionId(cleanCart.session_id);
      applyQuantities(cartToQuantities(cleanCart));
    } catch (error) {
      if (cartVersionRef.current !== version) return;
      console.error('Cart: Failed to fetch cart:', error);
    } finally {
      setLoading(false);
      setInitialized(true);
    }
  }, [user?.token, authLoading]);

  useEffect(() => {
    fetchCart();
  }, [fetchCart]);

  const getQuantity = useCallback((productId: number) => quantities[productId] ?? 0, [quantities]);

  const applyProductQuantity = (productId: number, quantity: number) => {
    const next = {
      ...quantitiesRef.current,
    };

    if (quantity <= 0) {
      delete next[productId];
    } else {
      next[productId] = quantity;
    }

    applyQuantities(next);
  };

  const enqueueCartMutation = async (
    mutationVersion: number,
    requestFn: () => Promise<ApiCart>,
    fallbackErrorMessage: string,
  ) => {
    const run = async () => {
      const result = await requestFn();

      const cleanCart = cleanCartData(result);

      if (cleanCart.session_id) {
        await saveSessionId(cleanCart.session_id);
      }

      if (cartMutationVersionRef.current !== mutationVersion) {
        return;
      }

      setCart(cleanCart);

      applyQuantities(cartToQuantities(cleanCart));
    };

    const queued = cartMutationQueueRef.current.then(run, run);

    cartMutationQueueRef.current = queued.catch(() => {});

    try {
      await queued;
    } catch (error: any) {
      console.error('Cart mutation failed:', error);

      if (cartMutationVersionRef.current !== mutationVersion) {
        return;
      }

      await fetchCart();

      Alert.alert('Внимание', error?.message || fallbackErrorMessage, [{ text: 'ОК' }]);
    }
  };

  const addToCart = async (productId: number, quantity = 1) => {
    const currentQuantity = quantitiesRef.current[productId] ?? 0;

    const targetQuantity = currentQuantity + quantity;

    if (targetQuantity <= 0) {
      await removeFromCart(productId);
      return;
    }

    cartVersionRef.current++;

    const mutationVersion = ++cartMutationVersionRef.current;

    applyProductQuantity(productId, targetQuantity);

    await enqueueCartMutation(
      mutationVersion,
      async () => {
        let sessionId = await getSessionId();

        if (!sessionId && cart?.session_id) {
          sessionId = cart.session_id;

          await saveSessionId(cart.session_id);
        }

        return ApiService.addToCart(productId, targetQuantity, user?.token, sessionId);
      },
      'Не удалось добавить товар в корзину',
    );
  };

  const removeFromCart = async (productId: number) => {
    cartVersionRef.current++;

    const mutationVersion = ++cartMutationVersionRef.current;

    applyProductQuantity(productId, 0);

    await enqueueCartMutation(
      mutationVersion,
      async () => {
        let sessionId = await getSessionId();

        if (!sessionId && cart?.session_id) {
          sessionId = cart.session_id;

          await saveSessionId(cart.session_id);
        }

        return ApiService.removeFromCart(productId, user?.token, sessionId);
      },
      'Не удалось удалить товар',
    );
  };

  const updateQuantity = async (productId: number, quantity: number) => {
    if (quantity <= 0) {
      await removeFromCart(productId);
      return;
    }

    cartVersionRef.current++;

    const mutationVersion = ++cartMutationVersionRef.current;

    applyProductQuantity(productId, quantity);

    await enqueueCartMutation(
      mutationVersion,
      async () => {
        let sessionId = await getSessionId();

        if (!sessionId && cart?.session_id) {
          sessionId = cart.session_id;

          await saveSessionId(cart.session_id);
        }

        return ApiService.addToCart(productId, quantity, user?.token, sessionId);
      },
      'Не удалось обновить количество',
    );
  };

  const clearCart = async () => {
    const prevQuantities = quantities;
    const prevCart = cart;

    setLoading(true);

    try {
      const sessionId = await getSessionId();
      const token = user?.token;

      await ApiService.clearCart(token, sessionId);

      applyQuantities({});
      setCart({
        total_amount: '0.00',
        total_items: 0,
        expires_at: '',
        session_id: sessionId || '',
        items: [],
      });
    } catch (error: any) {
      applyQuantities(prevQuantities);
      setCart(prevCart);

      Alert.alert(
        'Внимание',
        `Не удалось очистить корзину: ${error?.message || 'Неизвестная ошибка'}`,
        [{ text: 'ОК' }],
      );
    } finally {
      setLoading(false);
      console.log('--- КОНЕЦ ОЧИСТКИ КОРЗИНЫ ---');
    }
  };

  return (
    <CartContext.Provider
      value={{
        cart,
        loading,
        initialized,
        quantities,
        getQuantity,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        refresh: fetchCart,
      }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  return useContext(CartContext);
}
