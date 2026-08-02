
import React from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Image } from 'react-native';
import { Stack, useRouter, useLocalSearchParams } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const ORDER_DETAILS = {
  id: '2845',
  status: 'Доставлен',
  date: '24 Окт, 14:30',
  items: [
    {
      id: '1',
      name: 'Авокадо Хасс',
      description: '2 шт • 0.5 кг',
      price: '500 ₽',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAvFwIr2ENcCkTMZDOSfZTCFSthLdYAb73KH_Jsz0v-pJqA7q77hmsoToOE56WcBsLhbJfxBAbOLHCIcz7ynRLEI42gw4vzDDncp4WuRSWL2c31QA2yxTbeKuXAsWnOS1t5g4ialvAiUHdGw0HyXNrCw1B3RcV5GX78pP45Q3Ok_5RtTsD9jEebKz7fnv5T_4XmXfg55T1Ld1nYxYdc57_-d_alTtrT4Iurbdth6dMv1N9nzFhLeX8S3OYhak1kuyr98pdXjxIV-j8'
    },
    {
      id: '2',
      name: 'Хлеб ржаной',
      description: '1 шт • 400 г',
      price: '120 ₽',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8IrY1cRcPTimi2Pv8OUyw03q9T1G4RDuEPiEQQRIz2FwRYSHzM54ulB3-n6HvN1DAvambhwh8Qu44f_kCSptAtetN6nAMMwAlB-frQoF5ygYNygpLP_tFaZsP_lVoUVrmoLEorzWV05lNfQykawK3kTFreDkTnuGPKATcU6B2SCtEmcjNWBLf0f-gR7jPGMBs8mMrs7549zcA9OUUngPHMGu7crG_0YL5a3-2nz5KDs75A5cnkBdxEHL0_9LMWtpY7tf0bXU-mR0'
    },
    {
      id: '3',
      name: 'Молоко 3.2%',
      description: '1 шт • 1 л',
      price: '140 ₽',
      image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuC27ZhAFYaSBmGa0Xm4EekFOWmCTVbIe-Bvy7gIDSh30sh_S3UiQo2rIp9vUx1CQWynyITsHKF_dcW8pPuVmpbyQLz2iK8J2hoBxhDyPB87IE3QNYD4y5lfFplaCAz3a-UMftKuUVF_mKHLJKvTzz_hoYcMqx0lytCxtYIiAZhZLsap1Clc2KjJIVvG0SrmyqigauXFkFmAj3i-wq3qLu8QB4x8yNrLcirf1JdHmMfpstYfdue_PPs8J60h9EwskR351GN8U7CIsEA'
    }
  ],
  delivery: {
    address: 'ул. Ленина, д. 45, кв. 12',
    method: 'Курьерская доставка',
    price: 'Бесплатно'
  },
  summary: {
    subtotal: '760 ₽',
    discount: '-0 ₽',
    delivery: '0 ₽',
    total: '760 ₽'
  }
};

export default function OrderDetailScreen() {
  const router = useRouter();
  const { id } = useLocalSearchParams();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top, backgroundColor: colors.background }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border, backgroundColor: colors.background }]}>
        <TouchableOpacity 
            style={styles.backButton}
            onPress={() => router.back()}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Детали заказа</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        contentContainerStyle={[styles.scrollContent, { paddingBottom: Math.max(insets.bottom, 20) }]}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.content}>
          <View style={styles.titleSection}>
            <ThemedText style={styles.orderNumber}>Заказ #{id || ORDER_DETAILS.id}</ThemedText>
            <View style={styles.statusRow}>
              <View style={[styles.statusBadge, { backgroundColor: 'rgba(19, 236, 91, 0.15)' }]}>
                <IconSymbol name="info.circle.fill" size={14} color="#10b981" />
                <ThemedText style={styles.statusText}>{ORDER_DETAILS.status}</ThemedText>
              </View>
              <ThemedText style={[styles.orderDate, { color: colors.textSub }]}>{ORDER_DETAILS.date}</ThemedText>
            </View>
          </View>

          <View style={styles.section}>
            <ThemedText style={styles.sectionTitle}>Товары ({ORDER_DETAILS.items.length})</ThemedText>
            <View style={styles.itemsList}>
              {ORDER_DETAILS.items.map((item) => (
                <View key={item.id} style={[styles.itemCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                  <Image source={{ uri: item.image }} style={styles.itemImage} />
                  <View style={styles.itemInfo}>
                    <ThemedText style={styles.itemName} numberOfLines={1}>{item.name}</ThemedText>
                    <ThemedText style={[styles.itemDesc, { color: colors.textSub }]}>{item.description}</ThemedText>
                  </View>
                  <ThemedText style={styles.itemPrice}>{item.price}</ThemedText>
                </View>
              ))}
            </View>
          </View>

          <View style={styles.section}>
            <ThemedText style={styles.sectionTitle}>Информация о доставке</ThemedText>
            <View style={[styles.deliveryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={styles.deliveryRow}>
                <View style={[styles.deliveryIcon, { backgroundColor: 'rgba(19, 236, 91, 0.15)' }]}>
                  <IconSymbol name="house.fill" size={20} color="#059669" />
                </View>
                <View style={styles.deliveryInfo}>
                  <ThemedText style={[styles.deliveryLabel, { color: colors.textSub }]}>АДРЕС ДОСТАВКИ</ThemedText>
                  <ThemedText style={styles.deliveryValue}>{ORDER_DETAILS.delivery.address}</ThemedText>
                </View>
              </View>
              <View style={[styles.divider, { backgroundColor: colors.border, marginVertical: 16 }]} />
              <View style={styles.deliveryRow}>
                <View style={[styles.deliveryIcon, { backgroundColor: 'rgba(19, 236, 91, 0.15)' }]}>
                  <IconSymbol name="bag.fill" size={20} color="#059669" />
                </View>
                <View style={styles.deliveryInfo}>
                    <View style={styles.rowBetween}>
                        <View>
                            <ThemedText style={[styles.deliveryLabel, { color: colors.textSub }]}>СПОСОБ</ThemedText>
                            <ThemedText style={styles.deliveryValue}>{ORDER_DETAILS.delivery.method}</ThemedText>
                        </View>
                        <View style={[styles.freeBadge, { backgroundColor: 'rgba(19, 236, 91, 0.15)' }]}>
                            <ThemedText style={styles.freeText}>{ORDER_DETAILS.delivery.price}</ThemedText>
                        </View>
                    </View>
                </View>
              </View>
            </View>
          </View>

          <View style={[styles.summaryCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
            <View style={styles.summaryRow}>
              <ThemedText style={[styles.summaryLabel, { color: colors.textSub }]}>Стоимость товаров</ThemedText>
              <ThemedText style={styles.summaryValue}>{ORDER_DETAILS.summary.subtotal}</ThemedText>
            </View>
            <View style={styles.summaryRow}>
              <ThemedText style={[styles.summaryLabel, { color: colors.textSub }]}>Скидка</ThemedText>
              <ThemedText style={styles.summaryValue}>{ORDER_DETAILS.summary.discount}</ThemedText>
            </View>
            <View style={styles.summaryRow}>
              <ThemedText style={[styles.summaryLabel, { color: colors.textSub }]}>Доставка</ThemedText>
              <ThemedText style={[styles.summaryValue, { color: '#10b981' }]}>{ORDER_DETAILS.summary.delivery}</ThemedText>
            </View>
            <View style={[styles.divider, { backgroundColor: colors.border, marginVertical: 8 }]} />
            <View style={styles.summaryRow}>
              <ThemedText style={styles.totalLabel}>Итого</ThemedText>
              <ThemedText style={styles.totalValue}>{ORDER_DETAILS.summary.total}</ThemedText>
            </View>
          </View>

          {/* Action Buttons moved inside ScrollView */}
          <View style={styles.actionButtons}>
            <TouchableOpacity 
              style={styles.repeatButton}
              onPress={() => router.push('/repeat-order')}
            >
              <IconSymbol name="arrow.clockwise" size={20} color="#102216" />
              <ThemedText style={styles.repeatButtonText}>Повторить заказ</ThemedText>
            </TouchableOpacity>
            <TouchableOpacity 
              style={[styles.cancelButton, { backgroundColor: colorScheme === 'dark' ? 'rgba(239, 68, 68, 0.1)' : '#fef2f2' }]}
              onPress={() => router.push({ pathname: '/cancel-order', params: { id: id || ORDER_DETAILS.id } })}
            >
              <IconSymbol name="xmark" size={20} color="#ef4444" />
              <ThemedText style={styles.cancelButtonText}>Отменить заказ</ThemedText>
            </TouchableOpacity>
          </View>
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
    paddingBottom: 20,
  },
  content: {
    padding: 16,
  },
  titleSection: {
    marginBottom: 24,
  },
  orderNumber: {
    fontSize: 28,
    fontWeight: '800',
  },
  statusRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 8,
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 20,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#059669',
  },
  orderDate: {
    fontSize: 14,
    fontWeight: '500',
  },
  section: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    marginBottom: 12,
  },
  itemsList: {
    gap: 12,
  },
  itemCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 16,
    borderWidth: 1,
  },
  itemImage: {
    width: 64,
    height: 64,
    borderRadius: 12,
    backgroundColor: '#f3f4f6',
  },
  itemInfo: {
    flex: 1,
    marginLeft: 12,
    justifyContent: 'center',
  },
  itemName: {
    fontSize: 16,
    fontWeight: '600',
  },
  itemDesc: {
    fontSize: 14,
    marginTop: 2,
  },
  itemPrice: {
    fontSize: 16,
    fontWeight: '700',
  },
  deliveryCard: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
  },
  deliveryRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  deliveryIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  deliveryInfo: {
    flex: 1,
  },
  deliveryLabel: {
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  deliveryValue: {
    fontSize: 15,
    fontWeight: '600',
    marginTop: 2,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  freeBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  freeText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#059669',
  },
  summaryCard: {
    padding: 20,
    borderRadius: 16,
    borderWidth: 1,
    gap: 12,
    marginBottom: 24,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryLabel: {
    fontSize: 14,
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '500',
  },
  divider: {
    height: 1,
  },
  totalLabel: {
    fontSize: 18,
    fontWeight: '700',
  },
  totalValue: {
    fontSize: 24,
    fontWeight: '800',
  },
  actionButtons: {
    gap: 12,
    marginTop: 8,
  },
  repeatButton: {
    backgroundColor: '#13ec5b',
    height: 56,
    borderRadius: 16,
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
  repeatButtonText: {
    color: '#102216',
    fontSize: 18,
    fontWeight: '700',
  },
  cancelButton: {
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
  },
  cancelButtonText: {
    color: '#ef4444',
    fontSize: 18,
    fontWeight: '700',
  },
});
