
import React, { useState, useMemo } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Image, ActivityIndicator } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const SHOPS = [
  { name: 'Магнит', icon: 'basket.fill', color: '#2563eb', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCVaFta3XwnHGjJrj2KKqXUXdlrHlMiFyC4rl32xBsO4-ymhdC32k9FwBHGtO4qOqpKPWEtSBssjFzuq6RP2yfRo_yd-x4SjGHxmmeKHYjvSpTq9tNgs4tzEACL1RSGkWF5dWgSTTyQgRo-kF6bmFUbl1bVQgvdkhU0h7JRBn3ow1gVzu0HLIpESzPPpKtC_tKE5muQGk4UgOiOP15jeGJOIyX6OXfiqjjsAYB20CLYHj3vrYQixtixj_VM3csX4vpswcRNWz0S980' },
  { name: 'ВкусВилл', icon: 'bag.fill', color: '#059669', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDPgq2y90ufFPWhQ1LPeU8m33PJPeJ8gWL_7nf6dA_KbBBLsCD3fmBNbD-NGA3V4OasqFsNHbFvquuxzzwVRuh0k7G-GgRO1_c1__oVkhVNSM56Jyqfl0bBu02TM3KGhIzmVxg3rHjQmb4b4UUFt1uylJzaeKw9Z1-QPoAqIigEFUu6GvbF0HrfeNPnLTdhgw_nN9FdQ_Wiiv-5sxJii0_P5xcCu_mzJXN--KYogEpXLB3IxLMzurZ0BdAo7itzPrdzFBbOXRvQIYM' },
  { name: 'Пятёрочка', icon: 'storefront.fill', color: '#dc2626', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDvhwPl3DrFbfTLsBcPj_hw7iBJ8w8vcShLMtF5M2ze92SRlyvo8I3b2ztlLcc5UkeKiKqzSAEsu0jWlJG6b9FgjhdnKr1Y7SdXmkPpcq5hDzjYJyO2IcXYwmLwpKYuk1a2uH65x6jPOY0TFvgtf6NUq5a6mvmjJ4Hhq5kXmtgwdNjRDHaoPntylrhqMYtjh_X69hvs7XuPNtQokEqXA2fAHCUSXfFy0SiEwmq0rhp2rfWl3ss3zs4DLu42DRJmTYJm7wdGa5EFBFU' },
  { name: 'Ашан', icon: 'drop.fill', color: '#2563eb', image: 'https://lh3.googleusercontent.com/aida-public/AB6AXuABhkXLISHl-a8bDiayY-BLYM0Z8wGmrv0HhXmx-5PS9RaDx96EFGdhclfijtmeb-Vnd0rzmLip6W7UNBYKoU1ABtO8ll8woInNLEJfuGV7wG6ErR6IpoZFGVCAF8k4m1uJ9iJx2BY-sSWfgHSs0DvN8Bo0vlGvp3wiQqi7_I11ZnpR4Btou_BELagoA7DE6fw289_9yoToIi93pJa3yoQMMSQ9YEtkYep1NMOZ53SLI-Vtix15PoXSERA5elrrdm86-SJPjS05s2c' },
];

const generateOrders = (count: number) => {
  return Array.from({ length: count }).map((_, index) => {
    const id = (3000 - index).toString();
    const shop = SHOPS[index % SHOPS.length];
    const date = index === 0 ? 'Сегодня, 19:45' : `${(index + 1) % 28 + 1} Окт, 12:${index < 10 ? '0' + index : index}`;
    const isCanceled = index % 7 === 0 && index !== 0;
    const isActive = index === 0;
    
    return {
      id,
      shop: shop.name,
      date,
      status: isActive ? 'В пути' : isCanceled ? 'Отменен' : 'Доставлен',
      statusColor: isActive ? '#2563eb' : isCanceled ? '#6b7280' : '#10b981',
      statusBg: isActive ? 'rgba(37, 99, 235, 0.1)' : isCanceled ? 'rgba(107, 114, 128, 0.1)' : 'rgba(16, 185, 129, 0.1)',
      items: 'Йогурт, Бананы, Яблоки, Молоко, Хлеб...',
      count: (index % 5) + 2,
      price: `${(index % 10 + 4) * 150} ₽`,
      image: shop.image,
      icon: shop.icon,
      iconColor: isCanceled ? '#6b7280' : shop.color,
      canceled: isCanceled,
      active: isActive
    };
  });
};

const INITIAL_ORDERS = generateOrders(25);
const ORDERS_PER_PAGE = 5;

export default function OrdersScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [filter, setFilter] = useState<'active' | 'history'>('history');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);

  const filteredOrders = useMemo(() => {
    return INITIAL_ORDERS.filter(order => {
      if (filter === 'active') {
        return order.active === true;
      } else {
        return order.active === false;
      }
    });
  }, [filter]);

  const paginatedOrders = useMemo(() => {
    if (filter === 'active') return filteredOrders;
    return filteredOrders.slice(0, page * ORDERS_PER_PAGE);
  }, [filteredOrders, page, filter]);

  const hasMore = paginatedOrders.length < filteredOrders.length;

  const loadMore = () => {
    if (loading || !hasMore) return;
    setLoading(true);
    // Имитация загрузки
    setTimeout(() => {
      setPage(prev => prev + 1);
      setLoading(false);
    }, 800);
  };

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
            style={styles.backButton}
            onPress={() => router.back()}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>История заказов</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        onScroll={({ nativeEvent }) => {
          const isCloseToBottom = nativeEvent.layoutMeasurement.height + nativeEvent.contentOffset.y >= nativeEvent.contentSize.height - 20;
          if (isCloseToBottom && hasMore) {
            loadMore();
          }
        }}
        scrollEventThrottle={400}
      >
        {/* Segmented Control */}
        <View style={styles.filterContainer}>
          <View style={[styles.filterBg, { backgroundColor: colorScheme === 'dark' ? '#1a3825' : '#e5e7eb' }]}>
            <TouchableOpacity 
              onPress={() => {
                setFilter('active');
                setPage(1);
              }}
              style={[
                styles.filterItem, 
                filter === 'active' && [styles.filterItemSelected, { backgroundColor: colorScheme === 'dark' ? '#2d4f38' : colors.surface }]
              ]}
            >
              <ThemedText style={[
                styles.filterText, 
                filter === 'active' ? styles.filterTextActive : { color: colors.textSub }
              ]}>
                Активные
              </ThemedText>
            </TouchableOpacity>
            <TouchableOpacity 
              onPress={() => {
                setFilter('history');
                setPage(1);
              }}
              style={[
                styles.filterItem, 
                filter === 'history' && [styles.filterItemSelected, { backgroundColor: colorScheme === 'dark' ? '#2d4f38' : colors.surface }]
              ]}
            >
              <ThemedText style={[
                styles.filterText, 
                filter === 'history' ? styles.filterTextActive : { color: colors.textSub }
              ]}>
                История
              </ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        {/* Orders List */}
        <View style={styles.listContainer}>
          {paginatedOrders.length > 0 ? (
            paginatedOrders.map((order) => (
              <View key={order.id} style={[styles.card, { backgroundColor: colors.surface, borderColor: colors.border, opacity: order.canceled ? 0.8 : 1 }]}>
                <View style={styles.cardHeader}>
                  <View style={styles.shopInfo}>
                    <View style={[styles.shopIcon, { backgroundColor: order.statusBg }]}>
                      <IconSymbol name={order.icon} size={20} color={order.iconColor} />
                    </View>
                    <View>
                      <ThemedText style={styles.shopName}>{order.shop}</ThemedText>
                      <ThemedText style={[styles.orderMeta, { color: colors.textSub }]}>
                        #{order.id} • {order.date}
                      </ThemedText>
                    </View>
                  </View>
                  <View style={[styles.statusBadge, { backgroundColor: order.statusBg }]}>
                    <ThemedText style={[styles.statusText, { color: order.statusColor }]}>
                      {order.status}
                    </ThemedText>
                  </View>
                </View>

                <View style={styles.cardBody}>
                  <Image 
                    source={{ uri: order.image }} 
                    style={[styles.productImage, order.canceled && { tintColor: 'gray' }]} 
                  />
                  <View style={styles.productInfo}>
                    <ThemedText numberOfLines={2} style={styles.itemsText}>
                      {order.items}
                    </ThemedText>
                    <ThemedText style={[styles.itemsCount, { color: colors.textSub }]}>
                      {order.count} товара
                    </ThemedText>
                    <ThemedText style={styles.priceText}>{order.price}</ThemedText>
                  </View>
                </View>

                <View style={[styles.divider, { backgroundColor: colors.border }]} />

                <TouchableOpacity 
                  onPress={() => router.push(`/order/${order.id}`)}
                  style={[styles.actionButton, { backgroundColor: colorScheme === 'dark' ? 'rgba(255,255,255,0.05)' : colors.background }]}
                >
                  <IconSymbol 
                    name="info.circle.fill" 
                    size={18} 
                    color={colors.text} 
                  />
                  <ThemedText style={styles.actionText}>
                    Подробнее
                  </ThemedText>
                </TouchableOpacity>
              </View>
            ))
          ) : (
            <View style={styles.emptyContainer}>
                <ThemedText style={{ color: colors.textSub }}>У вас нет {filter === 'active' ? 'активных заказов' : 'истории заказов'}</ThemedText>
            </View>
          )}

          {loading && (
            <View style={styles.loaderContainer}>
              <ActivityIndicator size="small" color={colors.primaryDark} />
              <ThemedText style={[styles.loaderText, { color: colors.textSub }]}>Загрузка...</ThemedText>
            </View>
          )}

          {!loading && hasMore && (
            <TouchableOpacity onPress={loadMore} style={styles.loadMoreButton}>
              <ThemedText style={[styles.loadMoreText, { color: colors.primaryDark }]}>Показать еще</ThemedText>
            </TouchableOpacity>
          )}
        </View>
        
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
    paddingBottom: 24,
  },
  filterContainer: {
    paddingHorizontal: 16,
    paddingVertical: 16,
  },
  filterBg: {
    flexDirection: 'row',
    height: 40,
    borderRadius: 12,
    padding: 2,
  },
  filterItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 10,
  },
  filterItemSelected: {
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
  },
  filterText: {
    fontSize: 14,
    fontWeight: '500',
  },
  filterTextActive: {
    fontWeight: '600',
  },
  listContainer: {
    paddingHorizontal: 16,
    gap: 16,
  },
  card: {
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  shopInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  shopIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  shopName: {
    fontSize: 16,
    fontWeight: '700',
  },
  orderMeta: {
    fontSize: 12,
    fontWeight: '500',
    marginTop: 2,
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '700',
  },
  cardBody: {
    flexDirection: 'row',
    gap: 12,
  },
  productImage: {
    width: 80,
    height: 80,
    borderRadius: 12,
    backgroundColor: '#f3f4f6',
  },
  productInfo: {
    flex: 1,
    justifyContent: 'space-between',
    paddingVertical: 2,
  },
  itemsText: {
    fontSize: 14,
    lineHeight: 20,
  },
  itemsCount: {
    fontSize: 12,
    marginTop: 4,
  },
  priceText: {
    fontSize: 16,
    fontWeight: '700',
    marginTop: 'auto',
  },
  divider: {
    height: 1,
    marginVertical: 12,
  },
  actionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingVertical: 10,
    borderRadius: 10,
  },
  actionText: {
    fontSize: 14,
    fontWeight: '600',
  },
  emptyContainer: {
    padding: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loaderContainer: {
    paddingVertical: 20,
    alignItems: 'center',
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 8,
  },
  loaderText: {
    fontSize: 14,
  },
  loadMoreButton: {
    paddingVertical: 16,
    alignItems: 'center',
  },
  loadMoreText: {
    fontSize: 16,
    fontWeight: '700',
  }
});
