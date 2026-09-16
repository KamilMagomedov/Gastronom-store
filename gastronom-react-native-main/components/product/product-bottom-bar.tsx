import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useCart } from '@/context/cart-context';

interface ProductBottomBarProps {
  productId?: number;
  stockQuantity?: number;
  available?: boolean;
  loadingAvailability?: boolean;
}

export function ProductBottomBar({
  productId,
  stockQuantity,
  available,
  loadingAvailability = false,
}: ProductBottomBarProps) {
  const colorScheme = useColorScheme() ?? 'light';

  const colors = Colors[colorScheme];

  const { getQuantity, addToCart, updateQuantity } = useCart();

  const quantity = productId ? getQuantity(productId) : 0;

  const isOutOfStock = available === false || stockQuantity === 0;

  const isLimitReached = typeof stockQuantity === 'number' && quantity >= stockQuantity;

  const handleAddToCart = () => {
    if (!productId || loadingAvailability || isOutOfStock) {
      return;
    }

    void addToCart(productId, 1);
  };

  const handleDecrease = () => {
    if (!productId || quantity <= 0) {
      return;
    }

    void updateQuantity(productId, quantity - 1);
  };

  const handleIncrease = () => {
    if (!productId || loadingAvailability || isOutOfStock || isLimitReached) {
      return;
    }

    void updateQuantity(productId, quantity + 1);
  };

  return (
    <View
      style={[
        styles.container,
        {
          backgroundColor: colors.background,
          borderTopColor: colors.border,
        },
      ]}
    >
      <View style={styles.content}>
        {quantity === 0 ? (
          <TouchableOpacity
            style={[
              styles.addToCartButton,
              {
                backgroundColor: colors.primary,
              },
              (isOutOfStock || loadingAvailability) && {
                opacity: 0.5,
              },
            ]}
            onPress={handleAddToCart}
            disabled={!productId || isOutOfStock || loadingAvailability}
          >
            <IconSymbol name="cart.fill" size={20} color="#102216" />

            <Text style={styles.addToCartText}>
              {loadingAvailability
                ? 'Проверяем наличие...'
                : isOutOfStock
                  ? 'Нет в наличии'
                  : 'В корзину'}
            </Text>
          </TouchableOpacity>
        ) : (
          <View
            style={[
              styles.quantitySelector,
              {
                backgroundColor: colors.surface,
              },
            ]}
          >
            <TouchableOpacity style={styles.quantityButton} onPress={handleDecrease}>
              <IconSymbol name="minus" size={20} color={colors.text} />
            </TouchableOpacity>

            <Text style={[styles.quantityText, { color: colors.text }]}>{quantity}</Text>

            <TouchableOpacity
              style={[
                styles.quantityButton,
                (isLimitReached || loadingAvailability) && {
                  opacity: 0.3,
                },
              ]}
              onPress={handleIncrease}
              disabled={isLimitReached || loadingAvailability || isOutOfStock}
            >
              <IconSymbol
                name="plus"
                size={20}
                color={isLimitReached ? colors.textSub : colors.text}
              />
            </TouchableOpacity>
          </View>
        )}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 34,
    borderTopWidth: 1,
    zIndex: 100,
  },

  content: {
    flexDirection: 'row',
  },

  quantitySelector: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    height: 56,
  },

  quantityButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    height: '100%',
  },

  quantityText: {
    fontSize: 18,
    fontWeight: '700',
    minWidth: 24,
    textAlign: 'center',
  },

  addToCartButton: {
    flex: 1,
    height: 56,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#13ec5b',
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },

  addToCartText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#102216',
  },
});
