import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  TextInput,
  Switch,
  Modal,
} from 'react-native';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { SafeAreaView } from 'react-native-safe-area-context';

interface FilterModalProps {
  visible: boolean;
  onClose: () => void;
}

export function FilterModal({ visible, onClose }: FilterModalProps) {
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [priceMin, setPriceMin] = useState('100');
  const [priceMax, setPriceMax] = useState('5000');
  const [onlyInStock, setOnlyInStock] = useState(true);
  const [withDiscount, setWithDiscount] = useState(false);
  const [selectedSort, setSelectedSort] = useState('popularity');

  const sortOptions = [
    { id: 'popularity', label: 'По популярности' },
    { id: 'cheap', label: 'Сначала дешевле' },
    { id: 'expensive', label: 'Сначала дороже' },
    { id: 'delivery', label: 'Время доставки' },
  ];

  const categories = [
    'Все', 'Овощи и фрукты', 'Молочные продукты', 'Хлеб и выпечка', 'Мясо', 'Напитки', 'Заморозка'
  ];

  return (
    <Modal visible={visible} animationType="slide" presentationStyle="pageSheet">
      <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]}>
        <View style={[styles.header, { borderBottomColor: colors.border, backgroundColor: colors.surface }]}>
          <TouchableOpacity onPress={onClose} style={styles.backButton}>
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Настройки поиска</Text>
          <TouchableOpacity>
            <Text style={[styles.resetButton, { color: colors.primary }]}>Сбросить</Text>
          </TouchableOpacity>
        </View>

        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          {/* Quick Filters */}
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.quickFilters}>
            <View style={styles.quickFilterItem}>
              <TouchableOpacity style={[styles.quickFilterIcon, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <IconSymbol name="slider.horizontal.3" size={24} color={colors.primary} />
              </TouchableOpacity>
              <Text style={[styles.quickFilterLabel, { color: colors.primary }]}>Очистить</Text>
            </View>
            <View style={styles.quickFilterItem}>
              <TouchableOpacity style={[styles.quickFilterIcon, { backgroundColor: colors.primary }]}>
                <IconSymbol name="tag.fill" size={24} color="#FFF" />
              </TouchableOpacity>
              <Text style={[styles.quickFilterLabel, { color: colors.textSub }]}>Акции</Text>
            </View>
            <View style={styles.quickFilterItem}>
              <TouchableOpacity style={[styles.quickFilterIcon, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <IconSymbol name="star.fill" size={24} color="#9ca3af" />
              </TouchableOpacity>
              <Text style={[styles.quickFilterLabel, { color: colors.textSub }]}>4.5+</Text>
            </View>
          </ScrollView>

          {/* Sorting */}
          <View style={[styles.section, { backgroundColor: colors.surface }]}>
            <View style={styles.sectionHeader}>
              <IconSymbol name="arrow.up.arrow.down" size={20} color={colors.textSub} style={styles.sectionIcon} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Сортировка</Text>
            </View>
            <View style={styles.optionsList}>
              {sortOptions.map((option, index) => (
                <View key={option.id}>
                  <TouchableOpacity 
                    style={styles.optionItem}
                    onPress={() => setSelectedSort(option.id)}
                  >
                    <Text style={[
                      styles.optionLabel, 
                      { color: selectedSort === option.id ? colors.primary : colors.text }
                    ]}>
                      {option.label}
                    </Text>
                    <View style={[
                      styles.radio, 
                      { borderColor: selectedSort === option.id ? colors.primary : colors.border }
                    ]}>
                      {selectedSort === option.id && <View style={[styles.radioInner, { backgroundColor: colors.primary }]} />}
                    </View>
                  </TouchableOpacity>
                  {index < sortOptions.length - 1 && <View style={[styles.divider, { backgroundColor: colors.border }]} />}
                </View>
              ))}
            </View>
          </View>

          {/* Price */}
          <View style={[styles.section, { backgroundColor: colors.surface }]}>
            <View style={styles.sectionHeader}>
              <IconSymbol name="banknote" size={20} color={colors.textSub} style={styles.sectionIcon} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Цена</Text>
            </View>
            <View style={styles.priceInputs}>
              <View style={styles.priceInputWrapper}>
                <Text style={[styles.inputLabel, { color: colors.textSub }]}>От</Text>
                <View style={[styles.inputContainer, { backgroundColor: colors.background }]}>
                  <TextInput 
                    value={priceMin}
                    onChangeText={setPriceMin}
                    keyboardType="numeric"
                    style={[styles.input, { color: colors.text }]}
                  />
                  <Text style={styles.currency}>₽</Text>
                </View>
              </View>
              <View style={styles.priceInputWrapper}>
                <Text style={[styles.inputLabel, { color: colors.textSub }]}>До</Text>
                <View style={[styles.inputContainer, { backgroundColor: colors.background }]}>
                  <TextInput 
                    value={priceMax}
                    onChangeText={setPriceMax}
                    keyboardType="numeric"
                    style={[styles.input, { color: colors.text }]}
                  />
                  <Text style={styles.currency}>₽</Text>
                </View>
              </View>
            </View>
          </View>

          {/* Categories */}
          <View style={styles.categoriesSection}>
            <Text style={[styles.categoriesTitle, { color: colors.text }]}>Категории</Text>
            <View style={styles.categoryChips}>
              {categories.map((cat, index) => (
                <TouchableOpacity 
                  key={cat} 
                  style={[
                    styles.categoryChip, 
                    { 
                      backgroundColor: index === 0 ? colors.primary : colors.surface,
                      borderColor: index === 0 ? colors.primary : colors.border
                    }
                  ]}
                >
                  <Text style={[
                    styles.categoryChipText, 
                    { color: index === 0 ? '#FFF' : colors.text }
                  ]}>
                    {cat}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Features */}
          <View style={[styles.section, { backgroundColor: colors.surface }]}>
            <View style={styles.sectionHeader}>
              <IconSymbol name="line.3.horizontal.decrease" size={20} color={colors.textSub} style={styles.sectionIcon} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Особенности</Text>
            </View>
            <View style={styles.featureItem}>
              <View>
                <Text style={[styles.featureLabel, { color: colors.text }]}>Только в наличии</Text>
                <Text style={[styles.featureSub, { color: colors.textSub }]}>Скрыть отсутствующие товары</Text>
              </View>
              <Switch 
                value={onlyInStock} 
                onValueChange={setOnlyInStock}
                trackColor={{ false: '#d1d5db', true: colors.primary }}
              />
            </View>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <View style={styles.featureItem}>
              <View>
                <Text style={[styles.featureLabel, { color: colors.text }]}>Со скидкой</Text>
                <Text style={[styles.featureSub, { color: colors.textSub }]}>Товары по акции</Text>
              </View>
              <Switch 
                value={withDiscount} 
                onValueChange={setWithDiscount}
                trackColor={{ false: '#d1d5db', true: colors.primary }}
              />
            </View>
          </View>

          {/* Brands */}
          <View style={[styles.section, { backgroundColor: colors.surface }]}>
            <View style={styles.sectionHeader}>
              <IconSymbol name="bag" size={20} color={colors.textSub} style={styles.sectionIcon} />
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Бренды</Text>
            </View>
            <View style={[styles.brandSearch, { backgroundColor: colors.background }]}>
              <IconSymbol name="magnifyingglass" size={18} color="#9ca3af" />
              <TextInput placeholder="Поиск бренда" placeholderTextColor="#9ca3af" style={styles.brandInput} />
            </View>
            <View style={styles.brandsList}>
              {['ВкусВилл', 'Мираторг', 'Простоквашино', 'Макфа'].map((brand, idx) => (
                <TouchableOpacity key={brand} style={styles.brandItem}>
                   <View style={[styles.checkbox, { borderColor: idx === 1 ? colors.primary : colors.border, backgroundColor: idx === 1 ? colors.primary : 'transparent' }]}>
                    {idx === 1 && <IconSymbol name="checkmark" size={12} color="#FFF" />}
                   </View>
                   <Text style={[styles.brandLabel, { color: colors.text }]}>{brand}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        </ScrollView>

        <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.border }]}>
          <TouchableOpacity style={[styles.applyButton, { backgroundColor: colors.primary }]} onPress={onClose}>
            <Text style={styles.applyButtonText}>Показать 128 товаров</Text>
            <IconSymbol name="arrow.right" size={16} color="#FFF" />
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    height: 64,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    borderBottomWidth: 1,
  },
  backButton: {
    padding: 8,
    marginLeft: -8,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  resetButton: {
    fontSize: 14,
    fontWeight: '600',
  },
  scrollContent: {
    padding: 16,
    gap: 24,
    paddingBottom: 120,
  },
  quickFilters: {
    marginHorizontal: -16,
    paddingHorizontal: 16,
  },
  quickFilterItem: {
    alignItems: 'center',
    gap: 8,
    marginRight: 16,
  },
  quickFilterIcon: {
    width: 56,
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  quickFilterLabel: {
    fontSize: 12,
    fontWeight: '500',
  },
  section: {
    borderRadius: 20,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 2,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionIcon: {
    marginRight: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
  },
  optionsList: {
    gap: 0,
  },
  optionItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
  },
  optionLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  radio: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  radioInner: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  divider: {
    height: 1,
    width: '100%',
  },
  priceInputs: {
    flexDirection: 'row',
    gap: 16,
  },
  priceInputWrapper: {
    flex: 1,
  },
  inputLabel: {
    fontSize: 12,
    marginBottom: 4,
  },
  inputContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 8,
    paddingHorizontal: 12,
    height: 40,
  },
  input: {
    flex: 1,
    fontSize: 14,
    fontWeight: '600',
  },
  currency: {
    color: '#9ca3af',
    fontSize: 14,
    marginLeft: 4,
  },
  categoriesSection: {
    gap: 12,
  },
  categoriesTitle: {
    fontSize: 16,
    fontWeight: '700',
    paddingHorizontal: 4,
  },
  categoryChips: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 9999,
    borderWidth: 1,
  },
  categoryChipText: {
    fontSize: 14,
    fontWeight: '500',
  },
  featureItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  featureLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  featureSub: {
    fontSize: 12,
  },
  brandSearch: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 44,
    marginBottom: 16,
  },
  brandInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 14,
  },
  brandsList: {
    gap: 12,
    maxHeight: 160,
  },
  brandItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  checkbox: {
    width: 20,
    height: 20,
    borderRadius: 4,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  brandLabel: {
    fontSize: 14,
  },
  footer: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    paddingBottom: 32,
    borderTopWidth: 1,
  },
  applyButton: {
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#22c55e',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  applyButtonText: {
    color: '#FFF',
    fontSize: 16,
    fontWeight: '700',
  },
});
