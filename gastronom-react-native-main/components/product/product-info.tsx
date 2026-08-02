import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

interface ProductInfoProps {
  name: string;
  price: number;
  oldPrice?: number;
  unit: string;
  rating: number;
  reviewsCount: number;
}

export function ProductInfo({ name, price, oldPrice, unit, rating, reviewsCount }: ProductInfoProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <View style={styles.container}>
      <Text style={[styles.title, { color: colors.text }]}>{name}</Text>
      
      <View style={styles.priceRow}>
        <View style={styles.priceContainer}>
          <View>
            <Text style={[styles.price, { color: colors.primary }]}>{price} ₽</Text>
            {oldPrice ? (
              <Text style={styles.oldPrice}>{oldPrice} ₽</Text>
            ) : null}
          </View>
          <Text style={[styles.unit, { color: colors.textSub }]}> / {unit}</Text>
        </View>

        <View style={[styles.ratingContainer, { backgroundColor: colors.surface }]}>
          <IconSymbol name="star.fill" size={18} color="#facc15" />
          <Text style={[styles.rating, { color: colors.text }]}>{rating}</Text>
          <Text style={[styles.reviews, { color: colors.textSub }]}>({reviewsCount})</Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    marginBottom: 24,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    lineHeight: 34,
    marginBottom: 8,
  },
  priceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
  priceContainer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
  },
  price: {
    fontSize: 30,
    fontWeight: '800',
  },
  unit: {
    fontSize: 16,
    fontWeight: '500',
    marginBottom: 4,
    marginLeft: 2,
  },
  oldPrice: {
    fontSize: 14,
    color: '#9ca3af',
    textDecorationLine: 'line-through',
    marginTop: 2,
  },
  ratingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    gap: 4,
  },
  rating: {
    fontSize: 14,
    fontWeight: '700',
  },
  reviews: {
    fontSize: 12,
    fontWeight: '500',
  },
});
