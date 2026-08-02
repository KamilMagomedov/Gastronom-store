import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useCart } from '@/context/cart-context';

interface ProductBottomBarProps {
  productId?: number;
}

export function ProductBottomBar({ productId }: ProductBottomBarProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [quantity, setQuantity] = useState(1);
  const { addToCart } = useCart();

  const handleAddToCart = () => {
    if (!productId) return;
    addToCart(productId, quantity);
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background, borderTopColor: colors.border }]}>
      <View style={styles.content}>
        <View style={[styles.quantitySelector, { backgroundColor: colors.surface }]}>
          <TouchableOpacity
            style={styles.quantityButton}
            onPress={() => setQuantity(Math.max(1, quantity - 1))}
          >
            <IconSymbol name="minus" size={20} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.quantityText, { color: colors.text }]}>{quantity}</Text>
          <TouchableOpacity
            style={styles.quantityButton}
            onPress={() => setQuantity(quantity + 1)}
          >
            <IconSymbol name="plus" size={20} color={colors.text} />
          </TouchableOpacity>
        </View>

        <TouchableOpacity
          style={[styles.addToCartButton, { backgroundColor: colors.primary }]}
          onPress={handleAddToCart}
          disabled={!productId}
        >
          <IconSymbol name="cart.fill" size={20} color="#102216" />
          <Text style={styles.addToCartText}>В корзину</Text>
        </TouchableOpacity>
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
  content: { flexDirection: 'row', gap: 16 },
  quantitySelector: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    height: 56,
    flex: 0.4,
  },
  quantityButton: { flex: 1, alignItems: 'center', justifyContent: 'center', height: '100%' },
  quantityText: { fontSize: 18, fontWeight: '700', minWidth: 24, textAlign: 'center' },
  addToCartButton: {
    flex: 0.6,
    height: 56,
    borderRadius: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  addToCartText: { fontSize: 16, fontWeight: '700', color: '#102216' },
});
