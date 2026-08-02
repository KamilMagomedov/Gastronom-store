import React from 'react';
import { ScrollView, TouchableOpacity, Text, StyleSheet, ActivityIndicator, View } from 'react-native';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ApiCategory } from '@/services/api';

interface CategoryListProps {
  categories: ApiCategory[];
  selectedId: number | null;
  onSelect: (id: number | null) => void;
  loading?: boolean;
}

const CATEGORY_EMOJIS: Record<string, string> = {
  'овощи': '🥕',
  'фрукты': '🍎',
  'мясо': '🥩',
  'молочные': '🥛',
  'хлеб': '🍞',
  'напитки': '🧃',
  'заморозка': '🧊',
  'рыба': '🐟',
  'сладкое': '🍫',
  'бакалея': '🌾',
};

function getCategoryEmoji(name: string): string {
  const key = Object.keys(CATEGORY_EMOJIS).find((k) =>
    name.toLowerCase().includes(k),
  );
  return key ? CATEGORY_EMOJIS[key] : '🛒';
}

export function CategoryList({ categories, selectedId, onSelect, loading }: CategoryListProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  if (loading) {
    return (
      <View style={styles.loader}>
        <ActivityIndicator color={colors.primary} />
      </View>
    );
  }

  const all = [{ id: null, name: 'Все', slug: 'all' }] as { id: number | null; name: string; slug: string }[];
  const items = [...all, ...categories];

  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.container}
    >
      {items.map((category) => {
        const isActive = category.id === selectedId;
        return (
          <TouchableOpacity
            key={category.id ?? 'all'}
            style={[
              styles.categoryButton,
              { backgroundColor: colors.surface, borderColor: colors.border },
              isActive && [styles.activeCategoryButton, { backgroundColor: colors.primary, borderColor: colors.primary }],
            ]}
            onPress={() => onSelect(category.id)}
          >
            <Text style={styles.categoryIcon}>
              {category.id === null ? '🛍️' : getCategoryEmoji(category.name)}
            </Text>
            <Text
              style={[
                styles.categoryButtonText,
                { color: colors.text },
                isActive && { color: '#102216', fontWeight: 'bold' },
              ]}
            >
              {category.name}
            </Text>
          </TouchableOpacity>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: 16,
    gap: 12,
  },
  loader: {
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  categoryButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 9999,
    borderWidth: 1,
  },
  activeCategoryButton: {
    shadowColor: 'rgba(19, 236, 91, 0.2)',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 1,
    shadowRadius: 3.84,
    elevation: 5,
  },
  categoryIcon: {
    fontSize: 18,
  },
  categoryButtonText: {
    fontSize: 14,
    fontWeight: '600',
  },
});
