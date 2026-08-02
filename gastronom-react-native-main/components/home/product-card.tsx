import React from 'react';
import { View, Text, StyleSheet, Image, TouchableOpacity } from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { ProductImagePlaceholder } from '@/components/ui/product-image-placeholder';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useRouter } from 'expo-router';
import { useCart } from '@/context/cart-context';

interface Product {
  id: string;
  slug: string;
  name: string;
  price: number;
  oldPrice?: number;
  unit: string;
  image: string;
  discount?: string;
  stock?: number;
}

export function ProductCard({ product, horizontal = false, style }: { product: Product; horizontal?: boolean; style?: object }) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const router = useRouter();
  const { getQuantity, addToCart, updateQuantity } = useCart();
  
  const productId = Number(product.id);
  const qty = getQuantity(productId);

  const stockAvailable = product.stock ?? 999;
  const isOutOfStock = stockAvailable <= 0;
  const isLimitReached = qty >= stockAvailable;

  const handlePress = () => {
    router.push({
      pathname: '/product/[id]',
      params: { id: product.slug }
    });
  };

  const handleAdd = (e: any) => {
    e.stopPropagation();
    if (isOutOfStock) return;
    addToCart(productId);
  };

  const handleIncrease = (e: any) => {
    e.stopPropagation();
    if (isLimitReached) return;
    updateQuantity(productId, qty + 1);
  };

  const handleDecrease = (e: any) => {
    e.stopPropagation();
    updateQuantity(productId, qty - 1);
  };

  if (horizontal) {
    return (
      <TouchableOpacity
        style={[styles.hContainer, { backgroundColor: colors.surface }, isOutOfStock && styles.disabledCard]}
        onPress={handlePress}
        disabled={isOutOfStock}
      >
        <View style={[styles.hImageContainer, { backgroundColor: colorScheme === 'light' ? '#F9FAFB' : '#1F2937' }]}>
          {product.discount && (
            <View style={styles.discountBadge}>
              <Text style={styles.discountText}>{product.discount}</Text>
            </View>
          )}
          {product.image
            ? <Image source={{ uri: product.image }} style={[styles.hImage, isOutOfStock && styles.blurImage]} resizeMode="contain" />
            : <ProductImagePlaceholder size="small" />
          }
          <TouchableOpacity style={styles.favoriteButton}>
            <IconSymbol name="heart" size={16} color={colors.textSub} />
          </TouchableOpacity>
        </View>
        <View style={styles.hInfo}>
          <View>
            <Text style={[styles.name, { color: isOutOfStock ? '#9ca3af' : colors.text }]} numberOfLines={1}>
              {product.name}
            </Text>
            <Text style={[styles.unit, { color: colors.textSub }]}>
              {isOutOfStock ? 'Нет в наличии' : product.unit}
            </Text>
          </View>
          <View style={styles.hFooter}>
            <View>
              <Text style={[styles.price, { color: isOutOfStock ? '#9ca3af' : colors.text }]}>{product.price}₽</Text>
              {product.oldPrice && (
                <Text style={styles.oldPrice}>{product.oldPrice}₽</Text>
              )}
            </View>
            
            {isOutOfStock ? (
              <View style={[styles.outOfStockBadge]}>
                <Text style={styles.outOfStockText}>Закончился</Text>
              </View>
            ) : qty === 0 ? (
              <TouchableOpacity
                style={[styles.addButton, { backgroundColor: colors.primary }]}
                onPress={handleAdd}
              >
                <Text style={styles.addButtonText}>Добавить</Text>
              </TouchableOpacity>
            ) : (
              <View style={[styles.hStepper, { backgroundColor: colors.primary }]}>
                <TouchableOpacity style={styles.stepperBtn} onPress={handleDecrease}>
                  <IconSymbol name="minus" size={16} color="#102216" />
                </TouchableOpacity>
                <Text style={styles.stepperQty}>{qty}</Text>
                <TouchableOpacity 
                  style={[styles.stepperBtn, isLimitReached && styles.disabledBtn]} 
                  onPress={handleIncrease}
                  disabled={isLimitReached}
                >
                  <IconSymbol name="plus" size={16} color={isLimitReached ? '#9ca3af' : '#102216'} />
                </TouchableOpacity>
              </View>
            )}
          </View>
        </View>
      </TouchableOpacity>
    );
  }

  // Вертикальная карточка
  return (
    <TouchableOpacity
      style={[styles.vContainer, { backgroundColor: colors.surface }, style, isOutOfStock && styles.disabledCard]}
      onPress={handlePress}
    >
      <View style={[styles.vImageContainer, { backgroundColor: colorScheme === 'light' ? '#F9FAFB' : '#1F2937' }]}>
        {product.discount && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountText}>{product.discount}</Text>
          </View>
        )}
        {product.image
          ? <Image source={{ uri: product.image }} style={[styles.vImage, isOutOfStock && styles.blurImage]} resizeMode="contain" />
          : <ProductImagePlaceholder size="large" />
        }
        <TouchableOpacity style={styles.vFavoriteButton}>
          <IconSymbol name="heart" size={18} color={colors.textSub} />
        </TouchableOpacity>
      </View>
      <View style={styles.vInfo}>
        <Text style={[styles.name, { color: isOutOfStock ? '#9ca3af' : colors.text }]} numberOfLines={1}>{product.name}</Text>
        <Text style={[styles.unit, { color: colors.textSub }]}>
          {isOutOfStock ? 'Нет в наличии' : product.unit}
        </Text>
        <View style={styles.vFooter}>
          <View>
            <Text style={[styles.price, { color: isOutOfStock ? '#9ca3af' : colors.text }]}>{product.price}₽</Text>
            {product.oldPrice && (
              <Text style={styles.oldPrice}>{product.oldPrice}₽</Text>
            )}
          </View>

          {isOutOfStock ? (
            <View style={styles.outOfStockBadgeSub}>
              <Text style={styles.outOfStockTextSub}>0 шт.</Text>
            </View>
          ) : qty === 0 ? (
            <TouchableOpacity
              style={[styles.vAddButton, { backgroundColor: colors.primary }]}
              onPress={handleAdd}
            >
              <IconSymbol name="plus" size={20} color="#102216" />
            </TouchableOpacity>
          ) : (
            <View style={[styles.vStepper, { backgroundColor: colors.primary }]}>
              <TouchableOpacity style={styles.vStepperBtn} onPress={handleDecrease}>
                <IconSymbol name="minus" size={14} color="#102216" />
              </TouchableOpacity>
              <Text style={styles.stepperQty}>{qty}</Text>
              <TouchableOpacity 
                style={[styles.vStepperBtn, isLimitReached && styles.disabledBtn]} 
                onPress={handleIncrease}
                disabled={isLimitReached}
              >
                <IconSymbol name="plus" size={14} color={isLimitReached ? '#9ca3af' : '#102216'} />
              </TouchableOpacity>
            </View>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  hContainer: {
    flexDirection: 'row',
    padding: 12,
    borderRadius: 16,
    gap: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  hImageContainer: {
    width: 96,
    height: 96,
    borderRadius: 8,
    position: 'relative',
    padding: 8,
  },
  hImage: {
    width: '100%',
    height: '100%',
  },
  hInfo: {
    flex: 1,
    justifyContent: 'space-between',
  },
  hFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  vContainer: {
    width: '47%',
    borderRadius: 16,
    padding: 12,
    gap: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  vImageContainer: {
    aspectRatio: 1,
    borderRadius: 12,
    padding: 16,
    position: 'relative',
  },
  vImage: {
    width: '100%',
    height: '100%',
  },
  vInfo: {
    gap: 4,
  },
  vFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 'auto',
  },
  name: {
    fontSize: 16,
    fontWeight: '700',
  },
  unit: {
    fontSize: 12,
    fontWeight: '500',
  },
  price: {
    fontSize: 18,
    fontWeight: '800',
  },
  oldPrice: {
    fontSize: 12,
    color: '#9ca3af',
    textDecorationLine: 'line-through',
  },
  discountBadge: {
    position: 'absolute',
    top: 4,
    left: 4,
    backgroundColor: '#ef4444',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    zIndex: 1,
  },
  discountText: {
    color: 'white',
    fontSize: 10,
    fontWeight: '700',
  },
  favoriteButton: {
    position: 'absolute',
    top: 4,
    right: 4,
    padding: 4,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.5)',
  },
  vFavoriteButton: {
    position: 'absolute',
    top: 8,
    right: 8,
    padding: 6,
    borderRadius: 15,
    backgroundColor: 'rgba(255,255,255,0.5)',
  },
  // Horizontal card controls
  addButton: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 12,
  },
  addButtonText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#102216',
  },
  hStepper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    overflow: 'hidden',
  },
  stepperBtn: {
    width: 36,
    height: 36,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepperQty: {
    minWidth: 24,
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '700',
    color: '#102216',
  },
  // Vertical card controls
  vAddButton: {
    width: 36,
    height: 36,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  vStepper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    overflow: 'hidden',
  },
  vStepperBtn: {
    width: 28,
    height: 28,
    alignItems: 'center',
    justifyContent: 'center',
  },
  disabledCard: {
    opacity: 0.6,
  },
  blurImage: {
    opacity: 0.5,
  },
  disabledBtn: {
    opacity: 0.3,
  },
  outOfStockBadge: {
    backgroundColor: '#e5e7eb',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
  },
  outOfStockText: {
    color: '#6b7280',
    fontSize: 12,
    fontWeight: '700',
  },
  outOfStockBadgeSub: {
    backgroundColor: '#f3f4f6',
    padding: 6,
    borderRadius: 8,
  },
  outOfStockTextSub: {
    color: '#9ca3af',
    fontSize: 12,
    fontWeight: '600',
  }
});
