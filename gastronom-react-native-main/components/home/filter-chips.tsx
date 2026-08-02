import React, { useState } from 'react';
import { View, ScrollView, StyleSheet, Text, TouchableOpacity } from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { FilterModal } from './filter-modal';

const FILTERS = [
  { id: 'sort', label: 'Сортировка', icon: 'line.3.horizontal.decrease' },
  { id: 'all', label: 'Все', active: true },
  { id: 'price', label: 'Цена', hasArrow: true },
  { id: 'brand', label: 'Бренд', hasArrow: true },
  { id: 'available', label: 'В наличии' },
];

export function FilterChips() {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [modalVisible, setModalVisible] = useState(false);

  return (
    <View style={[styles.outerContainer, { borderBottomColor: colors.border }]}>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.container}
      >
        {FILTERS.map((filter) => (
          <TouchableOpacity
            key={filter.id}
            onPress={() => setModalVisible(true)}
            style={[
              styles.chip,
              {
                backgroundColor: filter.active ? colors.primary : colors.surface,
                borderColor: filter.active ? colors.primary : colors.border,
              },
            ]}
          >
            {filter.icon && (
              <IconSymbol name={filter.icon as any} size={16} color={filter.active ? '#102216' : colors.text} style={styles.icon} />
            )}
            <Text
              style={[
                styles.label,
                { color: filter.active ? '#102216' : colors.text, fontWeight: filter.active ? '700' : '700' },
              ]}
            >
              {filter.label}
            </Text>
            {filter.hasArrow && (
              <IconSymbol name="chevron.down" size={14} color={colors.text} style={styles.arrowIcon} />
            )}
          </TouchableOpacity>
        ))}
      </ScrollView>
      
      <FilterModal 
        visible={modalVisible} 
        onClose={() => setModalVisible(false)} 
      />
    </View>
  );
}

const styles = StyleSheet.create({
  outerContainer: {
    paddingVertical: 8,
    borderBottomWidth: 1,
  },
  container: {
    paddingHorizontal: 16,
    gap: 8,
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 9999,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  icon: {
    marginRight: 4,
  },
  arrowIcon: {
    marginLeft: 4,
  },
  label: {
    fontSize: 12,
  },
});
