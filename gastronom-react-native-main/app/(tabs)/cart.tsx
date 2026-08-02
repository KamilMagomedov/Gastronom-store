import { IconSymbol } from '@/components/ui/icon-symbol';
import { ProductImagePlaceholder } from '@/components/ui/product-image-placeholder';
import { Colors } from '@/constants/theme';
import { useCart } from '@/context/cart-context';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ApiCartItem, ApiService, AppSettings } from '@/services/api';
import { useFocusEffect, useRouter } from 'expo-router';
import React, { useCallback, useEffect, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    Image,
    Platform,
    RefreshControl,
    SafeAreaView,
    ScrollView,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';

function CartItemRow({ item, onIncrease, onDecrease, onRemove }: {
  item: ApiCartItem;
  onIncrease: () => void;
  onDecrease: () => void;
  onRemove: () => void;
}) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const image = item.product.images?.[0];

  return (
    <View style={[styles.itemCard, { backgroundColor: colors.surface }]}>
      <View style={styles.itemInfo}>
        <View style={[styles.itemImageWrap, { backgroundColor: colorScheme === 'light' ? '#F9FAFB' : '#1F2937' }]}>
          {image
            ? <Image source={{ uri: image }} style={styles.itemImage} resizeMode="contain" />
            : <ProductImagePlaceholder size="small" />
          }
        </View>
        <View style={styles.itemDetails}>
          <Text style={[styles.itemName, { color: colors.text }]} numberOfLines={2}>
            {item.product.name}
          </Text>
          <Text style={[styles.itemPrice, { color: colors.textSub }]}>
            {parseFloat(item.price).toFixed(0)} ₽ / {item.product.unit}
          </Text>
        </View>
        <TouchableOpacity onPress={onRemove} style={styles.removeBtn} hitSlop={8}>
          <IconSymbol name="xmark" size={14} color={colors.textSub} />
        </TouchableOpacity>
      </View>
      <View style={styles.itemActions}>
        <View style={[styles.quantitySelector, {
          backgroundColor: colorScheme === 'dark' ? '#1a3324' : '#f8fafc',
          borderColor: colors.border,
        }]}>
          <TouchableOpacity style={styles.qtyBtn} onPress={onDecrease}>
            <IconSymbol name="minus" size={18} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.qtyText, { color: colors.text }]}>{item.quantity}</Text>
          <TouchableOpacity style={[styles.qtyBtn, { backgroundColor: colors.primary }]} onPress={onIncrease}>
            <IconSymbol name="plus" size={18} color="#102216" />
          </TouchableOpacity>
        </View>
        <Text style={[styles.itemTotal, { color: colors.text }]}>
          {parseFloat(item.total).toFixed(0)} ₽
        </Text>
      </View>
    </View>
  );
}

export default function CartScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { cart, loading, initialized, addToCart, removeFromCart, updateQuantity, clearCart, refresh } = useCart();

  useFocusEffect(
    useCallback(() => {
      refresh();
    }, [refresh])
  );
  const [settings, setSettings] = useState<AppSettings | null>(null);
  const [settingsLoading, setSettingsLoading] = useState(true);

  const items = cart?.items ?? [];
  const totalAmount = cart ? parseFloat(cart.total_amount) : 0;
  const minOrderAmount = settings ? parseFloat(settings.delivery_settings.min_order_amount) : 0;
  const canCheckout = totalAmount >= minOrderAmount;

  useEffect(() => {
    const loadSettings = async () => {
      try {
        console.log('Cart: Loading app settings...');
        const response = await ApiService.getSettings();
        console.log('Cart: Settings loaded successfully');
        setSettings(response.data);
      } catch (error) {
        console.error('Cart: Failed to load settings:', error);
      } finally {
        setSettingsLoading(false);
      }
    };

    loadSettings();
  }, []);

  const handleClearCart = () => {
    clearCart();
  };

  const handleCheckout = () => {
    if (!canCheckout && settings) {
      Alert.alert(
        'Minimum Order Amount',
        `Minimum order amount is ${settings.delivery_settings.min_order_amount} ${settings.currency}. Add ${Math.abs(minOrderAmount - totalAmount).toFixed(2)} ${settings.currency} more to proceed.`,
        [{ text: 'OK' }]
      );
      return;
    }
    router.push('/checkout' as any);
  };

  if (!initialized || (loading && !cart)) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]}>
        <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Корзина</Text>
        </View>
        <View style={styles.centerFill}>
          <ActivityIndicator color={colors.primary} size="large" />
        </View>
      </SafeAreaView>
    );
  }

  if (items.length === 0) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]}>
        <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Корзина</Text>
        </View>
        <View style={styles.centerFill}>
          <IconSymbol name="cart" size={64} color={colors.textSub} />
          <Text style={[styles.emptyTitle, { color: colors.text }]}>Корзина пуста</Text>
          <Text style={[styles.emptySubtitle, { color: colors.textSub }]}>Добавьте товары из каталога</Text>
          <TouchableOpacity
            style={[styles.shopBtn, { backgroundColor: colors.primary }]}
            onPress={() => router.push('/(tabs)')}
          >
            <Text style={styles.shopBtnText}>Перейти в каталог</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <View style={styles.headerLeft}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backButton}>
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Корзина</Text>
        </View>
        
        <TouchableOpacity 
          onPress={handleClearCart} 
          hitSlop={{ top: 15, bottom: 15, left: 15, right: 15 }}
          style={{ zIndex: 999, padding: 5 }}
        >
          <Text style={[styles.clearButton, { color: colors.primaryDark }]}>Очистить</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={<RefreshControl refreshing={loading} onRefresh={refresh} tintColor={colors.primary} />}
      >
        <View style={styles.itemsList}>
          {items.map((item) => (
            <CartItemRow
              key={item.product.id}
              item={item}
              onIncrease={() => updateQuantity(item.product.id, item.quantity + 1)}
              onDecrease={() => updateQuantity(item.product.id, item.quantity - 1)}
              onRemove={() => removeFromCart(item.product.id)}
            />
          ))}
        </View>

        <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <Text style={[styles.summaryTitle, { color: colors.text }]}>Детали заказа</Text>
          <View style={styles.summaryRow}>
            <Text style={[styles.summaryLabel, { color: colors.textSub }]}>
              Товары ({cart?.total_items ?? 0})
            </Text>
            <Text style={[styles.summaryValue, { color: colors.text }]}>{totalAmount.toFixed(0)} ₽</Text>
          </View>
          
          {settings && (
            <>
              <View style={styles.summaryRow}>
                <Text style={[styles.summaryLabel, { color: colors.textSub }]}>Доставка</Text>
                <Text style={[styles.summaryValue, { color: colors.primaryDark }]}>
                  {totalAmount >= parseFloat(settings.delivery_settings.free_delivery_threshold) 
                    ? 'Бесплатно' 
                    : `${settings.delivery_settings.delivery_fee} ₽`}
                </Text>
              </View>
              
              {!canCheckout && (
                <View style={[styles.warningRow, { backgroundColor: '#FEF3C7' }]}>
                  <IconSymbol name="exclamationmark.triangle" size={16} color="#D97706" />
                  <Text style={[styles.warningText, { color: '#92400E' }]}>
                    Минимальный заказ: {settings.delivery_settings.min_order_amount} ₽
                  </Text>
                </View>
              )}
            </>
          )}
          
          <View style={[styles.divider, { backgroundColor: colors.border }]} />
          <View style={styles.summaryRow}>
            <Text style={[styles.totalLabel, { color: colors.text }]}>Итого</Text>
            <Text style={[styles.totalValue, { color: colors.text }]}>
              {settings && totalAmount < parseFloat(settings.delivery_settings.free_delivery_threshold)
                ? (totalAmount + parseFloat(settings.delivery_settings.delivery_fee)).toFixed(0)
                : totalAmount.toFixed(0)} ₽
            </Text>
          </View>
        </View>
      </ScrollView>

      <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.border }]}>
        <TouchableOpacity
          disabled={!canCheckout}
          style={[
            styles.checkoutButton, 
            { backgroundColor: colors.primary },
            !canCheckout && { opacity: 0.4, shadowOpacity: 0, elevation: 0 }
          ]}
          onPress={handleCheckout}
        >
          <Text style={styles.checkoutButtonText}>Оформить заказ</Text>
          <View style={styles.checkoutButtonPrice}>
            <Text style={styles.priceBadgeText}>
              {settings && totalAmount < parseFloat(settings.delivery_settings.free_delivery_threshold)
                ? (totalAmount + parseFloat(settings.delivery_settings.delivery_fee)).toFixed(0)
                : totalAmount.toFixed(0)} ₽
            </Text>
            <IconSymbol name="chevron.right" size={20} color="#102216" />
          </View>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { 
    flex: 1 
  },
  centerFill: { 
    flex: 1, 
    alignItems: 'center', 
    justifyContent: 'center', 
    gap: 16, 
    padding: 24 
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    ...Platform.select({
      ios: { paddingTop: 12 },
      android: { paddingTop: 40 },
    }),
  },
  headerLeft: { 
    flexDirection: 'row', 
    alignItems: 'center',
    gap: 12 
  },
  backButton: { 
    width: 40, 
    height: 40, 
    borderRadius: 20, 
    alignItems: 'center', 
    justifyContent: 'center' 
  },
  headerTitle: { 
    fontSize: 20, 
    fontWeight: '700' 
  },
  clearButton: { 
    fontSize: 14, 
    fontWeight: '600' 
  },
  scrollContent: { 
    padding: 16, 
    paddingBottom: 120 
  },
  itemsList: { 
    gap: 12, 
    marginBottom: 24 
  },
  itemCard: {
    padding: 16,
    borderRadius: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  itemInfo: { 
    flexDirection: 'row', 
    gap: 12, 
    marginBottom: 12, 
    alignItems: 'flex-start' 
  },
  itemImageWrap: { 
    width: 72, 
    height: 72, 
    borderRadius: 10, 
    overflow: 'hidden' 
  },
  itemImage: { 
    width: '100%', 
    height: '100%' 
  },
  itemDetails: { 
    flex: 1 
  },
  itemName: { 
    fontSize: 15, 
    fontWeight: '700', 
    marginBottom: 4 
  },
  itemPrice: { 
    fontSize: 13, 
    fontWeight: '500' 
  },
  removeBtn: { 
    padding: 4 
  },
  itemActions: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center' 
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 4,
    borderRadius: 10,
    borderWidth: 1,
  },
  qtyBtn: { 
    width: 32, 
    height: 32, 
    borderRadius: 8, 
    alignItems: 'center', 
    justifyContent: 'center' 
  },
  qtyText: { 
    width: 40, 
    textAlign: 'center', 
    fontSize: 16, 
    fontWeight: '700' 
  },
  itemTotal: { 
    fontSize: 18, 
    fontWeight: '700' 
  },
  summaryCard: { 
    padding: 20, 
    borderRadius: 16, 
    borderWidth: 1 
  },
  summaryTitle: { 
    fontSize: 18, 
    fontWeight: '700', 
    marginBottom: 16 
  },
  summaryRow: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    marginBottom: 12 
  },
  summaryLabel: { 
    fontSize: 14, 
    fontWeight: '500' 
  },
  summaryValue: { 
    fontSize: 14, 
    fontWeight: '600' 
  },
  divider: { 
    height: 1, 
    marginVertical: 16 
  },
  totalLabel: { 
    fontSize: 16, 
    fontWeight: '700' 
  },
  totalValue: { 
    fontSize: 20, 
    fontWeight: '800' 
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    paddingBottom: Platform.OS === 'ios' ? 34 : 16,
    borderTopWidth: 1,
  },
  checkoutButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 16,
    paddingHorizontal: 24,
    borderRadius: 16,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  checkoutButtonText: { 
    color: '#102216', 
    fontSize: 18, 
    fontWeight: '700' 
  },
  checkoutButtonPrice: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    gap: 8 
  },
  priceBadgeText: {
    backgroundColor: 'rgba(0,0,0,0.1)',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
    color: '#102216',
    fontSize: 16,
    fontWeight: '600',
  },
  emptyTitle: { 
    fontSize: 20, 
    fontWeight: '700' 
  },
  emptySubtitle: { 
    fontSize: 14 
  },
  shopBtn: { 
    paddingHorizontal: 32, 
    paddingVertical: 12, 
    borderRadius: 16, 
    marginTop: 8 
  },
  shopBtnText: { 
    color: '#102216', 
    fontWeight: '700', 
    fontSize: 16 
  },
  warningRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    padding: 12,
    borderRadius: 8,
    marginBottom: 12,
  },
  warningText: { 
    fontSize: 13, 
    fontWeight: '600' 
  },
});
