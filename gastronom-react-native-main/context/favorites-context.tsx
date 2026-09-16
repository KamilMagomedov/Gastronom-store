import { useAuth } from '@/context/auth-context';
import { ApiProduct, ApiService } from '@/services/api';
import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { Alert } from 'react-native';

interface FavoritesContextType {
  favorites: ApiProduct[];
  loading: boolean;
  initialized: boolean;
  isFavorite: (productId: number) => boolean;
  isUpdating: (productId: number) => boolean;
  addFavorite: (productId: number) => Promise<void>;
  removeFavorite: (productId: number) => Promise<void>;
  toggleFavorite: (productId: number) => Promise<void>;
  refresh: () => Promise<void>;
  clearFavorites: () => Promise<void>;
}

const FavoritesContext = createContext<FavoritesContextType>({
  favorites: [],
  loading: false,
  initialized: false,
  isFavorite: () => false,
  isUpdating: () => false,
  addFavorite: async () => {},
  removeFavorite: async () => {},
  toggleFavorite: async () => {},
  refresh: async () => {},
  clearFavorites: async () => {},
});

export function FavoritesProvider({ children }: { children: React.ReactNode }) {
  const { user, isLoading: authLoading } = useAuth();

  const [favorites, setFavorites] = useState<ApiProduct[]>([]);

  const [loading, setLoading] = useState(false);

  const [initialized, setInitialized] = useState(false);

  const [updatingIds, setUpdatingIds] = useState<Set<number>>(new Set());

  const refresh = useCallback(async () => {
    if (authLoading) {
      return;
    }

    if (!user?.token) {
      setFavorites([]);
      setInitialized(true);
      return;
    }

    setLoading(true);

    try {
      const response = await ApiService.getFavorites(user.token);

      setFavorites(Array.isArray(response.data) ? response.data : []);
    } catch (error) {
      console.error('Favorites: failed to load favorites', error);
    } finally {
      setLoading(false);
      setInitialized(true);
    }
  }, [authLoading, user?.token]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const isFavorite = useCallback(
    (productId: number) => favorites.some((product) => product.id === productId),
    [favorites],
  );

  const isUpdating = useCallback((productId: number) => updatingIds.has(productId), [updatingIds]);

  const startUpdating = (productId: number) => {
    setUpdatingIds((current) => {
      const next = new Set(current);
      next.add(productId);
      return next;
    });
  };

  const stopUpdating = (productId: number) => {
    setUpdatingIds((current) => {
      const next = new Set(current);
      next.delete(productId);
      return next;
    });
  };

  const addFavorite = async (productId: number) => {
    if (!user?.token || updatingIds.has(productId) || isFavorite(productId)) {
      return;
    }

    startUpdating(productId);

    try {
      const response = await ApiService.addFavorite(productId, user.token);

      setFavorites((current) => {
        if (current.some((product) => product.id === productId)) {
          return current;
        }

        return [response.data, ...current];
      });
    } catch (error: any) {
      console.error('Favorites: failed to add favorite', error);

      Alert.alert('Внимание', error?.message || 'Не удалось добавить товар в избранное');
    } finally {
      stopUpdating(productId);
    }
  };

  const removeFavorite = async (productId: number) => {
    if (!user?.token || updatingIds.has(productId)) {
      return;
    }

    startUpdating(productId);

    try {
      await ApiService.removeFavorite(productId, user.token);

      setFavorites((current) => current.filter((product) => product.id !== productId));
    } catch (error: any) {
      console.error('Favorites: failed to remove favorite', error);

      Alert.alert('Внимание', error?.message || 'Не удалось удалить товар из избранного');
    } finally {
      stopUpdating(productId);
    }
  };

  const toggleFavorite = async (productId: number) => {
    if (isFavorite(productId)) {
      await removeFavorite(productId);
      return;
    }

    await addFavorite(productId);
  };

  const clearFavorites = async () => {
    if (!user?.token || favorites.length === 0) {
      return;
    }

    const productIds = favorites.map((product) => product.id);

    setUpdatingIds((current) => {
      const next = new Set(current);

      productIds.forEach((productId) => {
        next.add(productId);
      });

      return next;
    });

    try {
      await Promise.all(
        productIds.map((productId) => ApiService.removeFavorite(productId, user.token)),
      );

      setFavorites([]);
    } catch (error: any) {
      console.error('Favorites: failed to clear favorites', error);

      Alert.alert('Внимание', error?.message || 'Не удалось очистить избранное');

      await refresh();
    } finally {
      setUpdatingIds((current) => {
        const next = new Set(current);

        productIds.forEach((productId) => {
          next.delete(productId);
        });

        return next;
      });
    }
  };

  return (
    <FavoritesContext.Provider
      value={{
        favorites,
        loading,
        initialized,
        isFavorite,
        isUpdating,
        addFavorite,
        removeFavorite,
        toggleFavorite,
        refresh,
        clearFavorites,
      }}
    >
      {children}
    </FavoritesContext.Provider>
  );
}

export function useFavorites() {
  return useContext(FavoritesContext);
}
