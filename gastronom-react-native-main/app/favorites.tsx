import React, { useState } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Image } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const FAVORITE_PRODUCTS = [
  {
    id: '1',
    name: 'Яблоки Гренни Смит, свежий урожай',
    weight: '~ 0.4 кг / шт',
    price: 120,
    oldPrice: 140,
    unit: 'кг',
    discount: '-15%',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuBcdGdCK9r0UggObxZVUvfa8iMZ9ukz8LL8WsM9YQUKpR98LFFQcMTT9z-h-gYDIGLhyGpXoj9gcznZifIySIdwlFzk9xi7Xi0rcUswwtRUu6fvh4MRmNSpn3z2yzPRPsmqMnKoQR7I9oJOIAJVjNFVQUFYCpZaMEJmLjT6-7_pOwgQvf5EZw4-4lkWr0esAMQmypTEMh9XfM0t9SPZnXhHAitPRuqffMr_CSXtTrdjd7nnWCugdq4zibzzFAEDkMdX-VGDkr7W8N0',
    category: 'Фрукты',
    inStock: true,
  },
  {
    id: '2',
    name: 'Молоко Домик в деревне 3.2% пастеризованное',
    weight: '900 мл',
    price: 89,
    unit: 'шт',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCWj4cto3AFX7R209kUbei7XHwGz2xruB4hj24dgsQapAObfM_1Vow5cZ9XzfwIJYD372jIjkzWuups-TDO2GKtI-zmm_o4-Kv-UicHZvJcbApYvu3SVHRETa0Ykxp7Lu-Iq9l65la58IlbMQrbp6mKtlAdfv5z4IyWxAP_RtORy32VlB3ZvG4XiPSOkx_IRFI_hjZB2VWSy1WS9bJHcIzA3lKlYa9qSfIkkGtl8bFb8QY61hEuksq_NvqnSAHnMqCrq_YCKUJ4Hls',
    category: 'Молочное',
    inStock: true,
    quantity: 1,
  },
  {
    id: '3',
    name: 'Авокадо Хасс спелое',
    weight: '1 шт',
    price: 240,
    unit: 'шт',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA-3lt7ocn4HyNFDxGk3qqik81ih2twOj6A5LCn5_vYUAafqSWmNhr7igqvlwuq5bk860RhsPHGFPKs7RwhiYrdTXiwDCtC52N3lSulp3Ta4sqfWlCjfNJi5yAApCWlq0zS5g3FmLonQYbDIyv5trv3xwoLjBjkQNkeGGKMYb9hStLTndp4GSOPxGXHIb7-8wSG2cqBHSOiWqVHjkjKrrc-Z13CTUmzlN7U-0n6-pGkMxCSe457ejJZHlHrM502yJfAqdB1f9OBDcM',
    category: 'Овощи',
    inStock: true,
  },
  {
    id: '4',
    name: 'Хлеб Бородинский в нарезке',
    weight: '400 г',
    price: 45,
    unit: 'шт',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGl-0PkxGmZXTYaMzkaVdYyt3Cj6CFOl2hKfwuTIpIsph7PvRmULHoDIhRpnSAKYmlaADIiKcZk4N5IuZViQw_HhdqwoMFr5VaiTryFkuvoPKjhHVMfVC4oF1HAZAWlb1AXPilL5C32MWzhNomfKoWlm0WGpBFnzUYvUX_oFmG8cPmvLjn8YVR0iwOLj5tryFMqIgWjghaQ6Sp5jaFbrXbGCI87vb-2yIfqOiS5qg9s5xoyohbFV3mzttl2qF8ghFhJJamM7jiIgE',
    category: 'Хлеб',
    inStock: false,
  },
  {
    id: '5',
    name: 'Сыр Пармезан выдержанный, 12 мес',
    weight: '200 г',
    price: 450,
    unit: 'шт',
    image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCAG61YlAHSME9tqYm-okAtbk772Esm0KHw6kIjVdE4I7aBuK9CDESFJtJQoeCCesBnZ9MlR5v0H1PVbMkQ93O1w5DTW0jBkGAmVHUNfW4C_HNF65DLcg_ajrb40ki4FwiAzUpKhsepvVXKi4aoc_kqmtXXRt1gqeshL72J4QpM858-B6_Id9Q5Js0cg05L9kIwE_L5a1moeeWebtOtcE5ty-bGG1a4cnDNS3CeQUFlxCdaB2WLeir-bDqT4Cr2rNK1UoflnJZWixM',
    category: 'Молочное',
    inStock: true,
  },
];

const CATEGORIES = ['Все', 'Молочное', 'Фрукты', 'Овощи', 'Напитки'];

export default function FavoritesScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [selectedCategory, setSelectedCategory] = useState('Все');

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Избранное</ThemedText>
        <TouchableOpacity style={styles.clearButton}>
          <ThemedText style={[styles.clearText, { color: colors.primaryDark }]}>Очистить</ThemedText>
        </TouchableOpacity>
      </View>

      {/* Filter Chips */}
      <View style={styles.filterSection}>
        <ScrollView 
          horizontal 
          showsHorizontalScrollIndicator={false} 
          contentContainerStyle={styles.filterScroll}
        >
          {CATEGORIES.map((category) => (
            <TouchableOpacity 
              key={category}
              onPress={() => setSelectedCategory(category)}
              style={[
                styles.chip, 
                { backgroundColor: selectedCategory === category ? colors.primary : colors.surface, 
                  borderColor: selectedCategory === category ? colors.primary : colors.border }
              ]}
            >
              <ThemedText style={[
                styles.chipText, 
                { color: selectedCategory === category ? '#102216' : colors.textSub,
                  fontWeight: selectedCategory === category ? '700' : '500' }
              ]}>
                {category}
              </ThemedText>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.productList}>
        {FAVORITE_PRODUCTS.filter(p => selectedCategory === 'Все' || p.category === selectedCategory).map((product) => (
          <TouchableOpacity 
            key={product.id} 
            activeOpacity={0.7}
            onPress={() => router.push(`/product/${product.id}`)}
            style={[styles.productCard, { backgroundColor: colors.surface, borderColor: colors.border, opacity: product.inStock ? 1 : 0.6 }]}
          >
            <TouchableOpacity 
              style={styles.favoriteIcon}
              onPress={(e) => {
                e.stopPropagation();
                // Логика удаления из избранного
              }}
            >
              <IconSymbol name="heart.fill" size={20} color="#ef4444" />
            </TouchableOpacity>

            <View style={styles.productContent}>
              <View style={styles.imageWrapper}>
                <Image source={{ uri: product.image }} style={[styles.productImage, !product.inStock && { grayscale: 1 } as any]} />
                {product.discount && (
                  <View style={styles.discountBadge}>
                    <ThemedText style={styles.discountText}>{product.discount}</ThemedText>
                  </View>
                )}
                {!product.inStock && (
                  <View style={styles.outOfStockOverlay}>
                    <ThemedText style={styles.outOfStockText}>Нет в наличии</ThemedText>
                  </View>
                )}
              </View>

              <View style={styles.productInfo}>
                <View style={{ paddingRight: 24 }}>
                  <ThemedText numberOfLines={2} style={styles.productName}>{product.name}</ThemedText>
                  <ThemedText style={[styles.productWeight, { color: colors.textSub }]}>{product.weight}</ThemedText>
                </View>

                <View style={styles.productFooter}>
                  <View>
                    <View style={styles.priceRow}>
                      <ThemedText style={[styles.productPrice, { color: colors.primaryDark }]}>{product.price} ₽</ThemedText>
                      {product.unit === 'кг' && <ThemedText style={[styles.unitText, { color: colors.textSub }]}> / кг</ThemedText>}
                    </View>
                    {product.oldPrice && (
                      <ThemedText style={styles.oldPrice}>{product.oldPrice} ₽</ThemedText>
                    )}
                  </View>

                  {product.inStock ? (
                    product.quantity ? (
                      <View 
                        style={[styles.quantitySelector, { backgroundColor: colorScheme === 'dark' ? '#2d4f38' : '#f3f4f6' }]}
                        onStartShouldSetResponder={() => true}
                        onTouchEnd={(e) => e.stopPropagation()}
                      >
                        <TouchableOpacity style={styles.qtyBtn}>
                          <ThemedText style={{ color: colors.text, fontWeight: '700' }}>-</ThemedText>
                        </TouchableOpacity>
                        <ThemedText style={[styles.qtyText, { color: colors.text }]}>{product.quantity}</ThemedText>
                        <TouchableOpacity style={styles.qtyBtn}>
                          <ThemedText style={{ color: colors.text, fontWeight: '700' }}>+</ThemedText>
                        </TouchableOpacity>
                      </View>
                    ) : (
                      <TouchableOpacity 
                        style={[styles.addToCartButton, { backgroundColor: colors.primary }]}
                        onPress={(e) => {
                          e.stopPropagation();
                          // Добавление в корзину
                        }}
                      >
                        <IconSymbol name="cart.fill" size={18} color="#102216" />
                      </TouchableOpacity>
                    )
                  ) : (
                    <TouchableOpacity 
                      style={[styles.notifyButton, { backgroundColor: colorScheme === 'dark' ? '#374151' : '#e5e7eb' }]}
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
        ))}
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
