
import React from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const ORDER_TO_REPEAT = {
  id: '2845',
  date: '14 октября',
  status: 'Доставлен',
  store: 'ВкусВилл',
  items: [
    { id: '1', name: 'Авокадо Хасс', quantity: '2 шт', weight: '400 г', price: '240 ₽', icon: '🥑' },
    { id: '2', name: 'Молоко 3.2%', quantity: '1 шт', weight: '900 мл', price: '89 ₽', icon: '🥛' },
    { id: '3', name: 'Хлеб Бородинский', quantity: '1 шт', weight: '350 г', price: '54 ₽', icon: '🍞' },
    { id: '4', name: 'Томаты черри', quantity: '1 уп', weight: '250 г', price: '180 ₽', icon: '🍅' },
  ],
  summary: {
    subtotal: '563 ₽',
    delivery: '0 ₽',
    total: '563 ₽'
  }
};

export default function RepeatOrderScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const handleRepeatOrder = () => {
    router.push('/order-success');
  };

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border, backgroundColor: colors.background }]}>
        <TouchableOpacity 
            style={styles.backButton}
            onPress={() => router.back()}
        >
          <IconSymbol name="chevron.left" size={28} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Повторить заказ</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        contentContainerStyle={[styles.scrollContent, { paddingBottom: Math.max(insets.bottom, 24) }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.content}>
          {/* Order Brief */}
          <View style={[styles.orderBrief, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={[styles.briefIconContainer, { backgroundColor: colorScheme === 'dark' ? colors.background : '#e0fdf0', borderColor: 'rgba(19, 236, 91, 0.2)' }]}>
              <IconSymbol name="bag.fill" size={24} color={colorScheme === 'dark' ? '#13ec5b' : '#0fb847'} />
            </View>
            <View style={styles.briefInfo}>
              <ThemedText style={styles.briefTitle}>Заказ от {ORDER_TO_REPEAT.date}</ThemedText>
              <ThemedText style={[styles.briefSubtitle, { color: colors.textSub }]}>
                {ORDER_TO_REPEAT.status} • {ORDER_TO_REPEAT.store}
              </ThemedText>
            </View>
          </View>

          {/* Section Title */}
          <View style={styles.sectionHeader}>
            <ThemedText style={styles.sectionTitle}>Товары в заказе</ThemedText>
            <ThemedText style={[styles.itemCount, { color: colors.textSub }]}>{ORDER_TO_REPEAT.items.length} товара</ThemedText>
          </View>

          {/* Items List */}
          <View style={[styles.itemsContainer, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            {ORDER_TO_REPEAT.items.map((item, index) => (
              <View 
                key={item.id} 
                style={[
                  styles.itemRow, 
                  index < ORDER_TO_REPEAT.items.length - 1 && { borderBottomColor: colors.border, borderBottomWidth: 1 }
                ]}
              >
                <View style={[styles.itemIconBox, { backgroundColor: colorScheme === 'dark' ? colors.background : '#f0f4f2' }]}>
                  <ThemedText style={styles.itemEmoji}>{item.icon}</ThemedText>
                </View>
                <View style={styles.itemMainInfo}>
                  <ThemedText style={styles.itemName}>{item.name}</ThemedText>
                  <ThemedText style={[styles.itemSpecs, { color: colors.textSub }]}>{item.quantity} • {item.weight}</ThemedText>
                </View>
                <ThemedText style={styles.itemPrice}>{item.price}</ThemedText>
              </View>
            ))}
          </View>

          {/* Price Summary */}
          <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.summaryRow}>
              <ThemedText style={[styles.summaryLabel, { color: colors.textSub }]}>Стоимость товаров</ThemedText>
              <ThemedText style={styles.summaryValue}>{ORDER_TO_REPEAT.summary.subtotal}</ThemedText>
            </View>
            <View style={styles.summaryRow}>
              <ThemedText style={[styles.summaryLabel, { color: colors.textSub }]}>Сборка и доставка</ThemedText>
              <ThemedText style={styles.summaryValue}>{ORDER_TO_REPEAT.summary.delivery}</ThemedText>
            </View>
            <View style={[styles.divider, { backgroundColor: colors.border }]} />
            <View style={styles.summaryRow}>
              <ThemedText style={styles.totalLabel}>Итого</ThemedText>
              <ThemedText style={styles.totalValue}>{ORDER_TO_REPEAT.summary.total}</ThemedText>
            </View>
          </View>

          {/* Repeat Order Button inside ScrollView */}
          <TouchableOpacity 
            style={styles.repeatButton} 
            activeOpacity={0.8}
            onPress={handleRepeatOrder}
          >
            <ThemedText style={styles.repeatButtonText}>Повторить заказ</ThemedText>
            <View style={styles.dot} />
            <ThemedText style={styles.repeatButtonText}>{ORDER_TO_REPEAT.summary.total}</ThemedText>
          </TouchableOpacity>
        </View>
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
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    flex: 1,
    textAlign: 'center',
  },
  scrollContent: {
    paddingTop: 16,
  },
  content: {
    paddingHorizontal: 16,
    gap: 16,
    paddingBottom: 16,
  },
  orderBrief: {
    flexDirection: 'row',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    gap: 16,
  },
  briefIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  briefInfo: {
    flex: 1,
  },
  briefTitle: {
    fontSize: 16,
    fontWeight: '700',
  },
  briefSubtitle: {
    fontSize: 14,
    fontWeight: '500',
    marginTop: 2,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-end',
    paddingHorizontal: 4,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  itemCount: {
    fontSize: 14,
    fontWeight: '500',
  },
  itemsContainer: {
    borderRadius: 16,
    borderWidth: 1,
    overflow: 'hidden',
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 16,
    gap: 16,
  },
  itemIconBox: {
    width: 56,
    height: 56,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  itemEmoji: {
    fontSize: 30,
  },
  itemMainInfo: {
    flex: 1,
  },
  itemName: {
    fontSize: 16,
    fontWeight: '700',
  },
  itemSpecs: {
    fontSize: 14,
    fontWeight: '500',
    marginTop: 4,
  },
  itemPrice: {
    fontSize: 16,
    fontWeight: '700',
  },
  summaryCard: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    gap: 12,
    marginBottom: 8,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '500',
  },
  divider: {
    height: 1,
    marginVertical: 4,
  },
  totalLabel: {
    fontSize: 18,
    fontWeight: '700',
  },
  totalValue: {
    fontSize: 18,
    fontWeight: '700',
  },
  repeatButton: {
    backgroundColor: '#13ec5b',
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
    marginTop: 8,
  },
  repeatButtonText: {
    color: '#111813',
    fontSize: 16,
    fontWeight: '700',
  },
  dot: {
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: '#111813',
    marginHorizontal: 4,
    opacity: 0.4,
  },
});
