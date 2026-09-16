import React, { useMemo, useState } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Image } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useFavorites } from '@/context/favorites-context';
import { useCart } from '@/context/cart-context';
import { ProductImagePlaceholder } from '@/components/ui/product-image-placeholder';

export default function FavoritesScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [selectedCategory, setSelectedCategory] = useState('Все');
  const { favorites, loading, removeFavorite, clearFavorites, isUpdating } = useFavorites();

  const { getQuantity, addToCart, updateQuantity } = useCart();

  const categories = useMemo(() => {
    const names = favorites
      .map((product) => product.category?.name)
      .filter((name): name is string => Boolean(name));

    return ['Все', ...Array.from(new Set(names))];
  }, [favorites]);

  const filteredFavorites = useMemo(() => {
    if (selectedCategory === 'Все') {
      return favorites;
    }

    return favorites.filter((product) => product.category?.name === selectedCategory);
  }, [favorites, selectedCategory]);

  const handleBack = () => {
    if (router.canGoBack()) {
      router.back();
      return;
    }

    router.replace('/profile');
  };

  const handleClearFavorites = () => {
    if (loading || favorites.length === 0) {
      return;
    }

    void clearFavorites();
  };

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />

      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity onPress={handleBack} style={styles.backButton}>
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Избранное</ThemedText>
        <TouchableOpacity
          style={[
            styles.clearButton,
            favorites.length === 0 && {
              opacity: 0.4,
            },
          ]}
          onPress={handleClearFavorites}
          disabled={loading || favorites.length === 0}
        >
          <ThemedText style={[styles.clearText, { color: colors.primaryDark }]}>
            Очистить
          </ThemedText>
        </TouchableOpacity>
      </View>

      <View style={styles.filterSection}>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.filterScroll}
        >
          {categories.map((category) => (
            <TouchableOpacity
              key={category}
              onPress={() => setSelectedCategory(category)}
              style={[
                styles.chip,
                {
                  backgroundColor: selectedCategory === category ? colors.primary : colors.surface,
                  borderColor: selectedCategory === category ? colors.primary : colors.border,
                },
              ]}
            >
              <ThemedText
                style={[
                  styles.chipText,
                  {
                    color: selectedCategory === category ? '#102216' : colors.textSub,
                    fontWeight: selectedCategory === category ? '700' : '500',
                  },
                ]}
              >
                {category}
              </ThemedText>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.productList}>
        {filteredFavorites.map((product) => {
          const quantity = getQuantity(product.id);

          const price = Number(product.price);

          const oldPrice = product.old_price ? Number(product.old_price) : null;

          const productWithStock = product as typeof product & {
            in_stock?: boolean;
            stock_quantity?: number;
          };

          const stockQuantity = productWithStock.stock_quantity;

          const inStock =
            productWithStock.in_stock !== false &&
            (typeof stockQuantity !== 'number' || stockQuantity > 0);

          const isLimitReached = typeof stockQuantity === 'number' && quantity >= stockQuantity;

          const discount =
            oldPrice && oldPrice > price
              ? `-${Math.round(((oldPrice - price) / oldPrice) * 100)}%`
              : null;

          return (
            <TouchableOpacity
              key={product.id}
              activeOpacity={0.7}
              onPress={() =>
                router.push({
                  pathname: '/product/[id]',
                  params: {
                    id: product.slug,
                  },
                })
              }
              style={[
                styles.productCard,
                {
                  backgroundColor: colors.surface,
                  borderColor: colors.border,
                  opacity: inStock ? 1 : 0.6,
                },
              ]}
            >
              <TouchableOpacity
                style={[
                  styles.favoriteIcon,
                  isUpdating(product.id) && {
                    opacity: 0.4,
                  },
                ]}
                onPress={(e) => {
                  e.stopPropagation();

                  void removeFavorite(product.id);
                }}
                disabled={isUpdating(product.id)}
              >
                <IconSymbol name="heart.fill" size={20} color="#ef4444" />
              </TouchableOpacity>

              <View style={styles.productContent}>
                <View style={styles.imageWrapper}>
                  {product.image ? (
                    <Image
                      source={{ uri: product.image }}
                      style={[
                        styles.productImage,
                        !inStock && {
                          opacity: 0.5,
                        },
                      ]}
                    />
                  ) : (
                    <View style={styles.productImage}>
                      <ProductImagePlaceholder size="small" />
                    </View>
                  )}
                  {discount && (
                    <View style={styles.discountBadge}>
                      <ThemedText style={styles.discountText}>{discount}</ThemedText>
                    </View>
                  )}
                  {!inStock && (
                    <View style={styles.outOfStockOverlay}>
                      <ThemedText style={styles.outOfStockText}>Нет в наличии</ThemedText>
                    </View>
                  )}
                </View>

                <View style={styles.productInfo}>
                  <View style={{ paddingRight: 24 }}>
                    <ThemedText numberOfLines={2} style={styles.productName}>
                      {product.name}
                    </ThemedText>
                    <ThemedText style={[styles.productWeight, { color: colors.textSub }]}>
                      {product.unit}
                    </ThemedText>
                  </View>

                  <View style={styles.productFooter}>
                    <View>
                      <View style={styles.priceRow}>
                        <ThemedText style={[styles.productPrice, { color: colors.primaryDark }]}>
                          {price.toFixed(2)} ₽
                        </ThemedText>
                        {product.unit === 'кг' && (
                          <ThemedText style={[styles.unitText, { color: colors.textSub }]}>
                            {' '}
                            / кг
                          </ThemedText>
                        )}
                      </View>
                      {oldPrice && (
                        <ThemedText style={styles.oldPrice}>{oldPrice.toFixed(2)} ₽</ThemedText>
                      )}
                    </View>

                    {inStock ? (
                      quantity > 0 ? (
                        <View
                          style={[
                            styles.quantitySelector,
                            { backgroundColor: colorScheme === 'dark' ? '#2d4f38' : '#f3f4f6' },
                          ]}
                          onStartShouldSetResponder={() => true}
                          onTouchEnd={(e) => e.stopPropagation()}
                        >
                          <TouchableOpacity
                            style={styles.qtyBtn}
                            onPress={(e) => {
                              e.stopPropagation();

                              void updateQuantity(product.id, quantity - 1);
                            }}
                          >
                            <ThemedText style={{ color: colors.text, fontWeight: '700' }}>
                              -
                            </ThemedText>
                          </TouchableOpacity>
                          <ThemedText style={[styles.qtyText, { color: colors.text }]}>
                            {quantity}
                          </ThemedText>
                          <TouchableOpacity
                            style={[
                              styles.qtyBtn,
                              isLimitReached && {
                                opacity: 0.3,
                              },
                            ]}
                            disabled={isLimitReached}
                            onPress={(e) => {
                              e.stopPropagation();

                              if (isLimitReached) {
                                return;
                              }

                              void updateQuantity(product.id, quantity + 1);
                            }}
                          >
                            <ThemedText style={{ color: colors.text, fontWeight: '700' }}>
                              +
                            </ThemedText>
                          </TouchableOpacity>
                        </View>
                      ) : (
                        <TouchableOpacity
                          style={[styles.addToCartButton, { backgroundColor: colors.primary }]}
                          onPress={(e) => {
                            e.stopPropagation();
                            void addToCart(product.id);
                          }}
                        >
                          <IconSymbol name="cart.fill" size={18} color="#102216" />
                        </TouchableOpacity>
                      )
                    ) : (
                      <TouchableOpacity
                        style={[
                          styles.notifyButton,
                          { backgroundColor: colorScheme === 'dark' ? '#374151' : '#e5e7eb' },
                        ]}
                        onPress={(e) => {
                          e.stopPropagation();
                          // Уведомление о наличии
                        }}
                      >
                        <IconSymbol name="bell.fill" size={18} color={colors.textSub} />
                      </TouchableOpacity>
                    )}
                  </View>
                </View>
              </View>
            </TouchableOpacity>
          );
        })}

        <View style={{ height: 40 }} />
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    height: 56,
    borderBottomWidth: 1,
  },
  backButton: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  clearButton: {
    paddingHorizontal: 8,
  },
  clearText: {
    fontSize: 14,
    fontWeight: '600',
  },
  filterSection: {
    paddingVertical: 12,
  },
  filterScroll: {
    paddingHorizontal: 16,
    gap: 8,
  },
  chip: {
    height: 36,
    paddingHorizontal: 16,
    borderRadius: 18,
    justifyContent: 'center',
    borderWidth: 1,
  },
  chipText: {
    fontSize: 14,
  },
  productList: {
    paddingHorizontal: 16,
    gap: 12,
  },
  productCard: {
    borderRadius: 20,
    padding: 12,
    borderWidth: 1,
    position: 'relative',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  favoriteIcon: {
    position: 'absolute',
    top: 12,
    right: 12,
    zIndex: 1,
    padding: 4,
  },
  productContent: {
    flexDirection: 'row',
    gap: 16,
  },
  imageWrapper: {
    position: 'relative',
  },
  productImage: {
    width: 88,
    height: 88,
    borderRadius: 16,
    backgroundColor: '#f3f4f6',
  },
  discountBadge: {
    position: 'absolute',
    top: -4,
    left: -4,
    backgroundColor: '#ef4444',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 8,
  },
  discountText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '800',
  },
  outOfStockOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.3)',
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 4,
  },
  outOfStockText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '800',
    textAlign: 'center',
    backgroundColor: 'rgba(31, 41, 55, 0.8)',
    paddingHorizontal: 4,
    paddingVertical: 2,
    borderRadius: 4,
  },
  productInfo: {
    flex: 1,
    justifyContent: 'space-between',
  },
  productName: {
    fontSize: 15,
    fontWeight: '600',
    lineHeight: 20,
  },
  productWeight: {
    fontSize: 12,
    marginTop: 2,
  },
  productFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-end',
    marginTop: 8,
  },
  priceRow: {
    flexDirection: 'row',
    alignItems: 'baseline',
  },
  productPrice: {
    fontSize: 18,
    fontWeight: '800',
  },
  unitText: {
    fontSize: 12,
  },
  oldPrice: {
    fontSize: 12,
    color: '#9ca3af',
    textDecorationLine: 'line-through',
    marginTop: -2,
  },
  addToCartButton: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 6,
    elevation: 4,
  },
  notifyButton: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 36,
    borderRadius: 10,
    paddingHorizontal: 4,
  },
  qtyBtn: {
    width: 28,
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
  },
  qtyText: {
    width: 20,
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '600',
  },
});
