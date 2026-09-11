import React, {
  useCallback,
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  ActivityIndicator,
  Image,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  View,
} from 'react-native';

import { Stack, useRouter } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';

import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';

import {
  ApiOrder,
  ApiService,
} from '@/services/api';

type OrderFilter = 'active' | 'history';

const ACTIVE_STATUSES = new Set([
  'pending',
  'confirmed',
  'preparing',
  'ready',
  'delivering',
]);

const STATUS_CONFIG: Record<
  string,
  {
    label: string;
    color: string;
    backgroundColor: string;
    icon: string;
  }
> = {
  pending: {
    label: 'Принят',
    color: '#d97706',
    backgroundColor: 'rgba(217, 119, 6, 0.12)',
    icon: 'clock.fill',
  },
  confirmed: {
    label: 'Подтверждён',
    color: '#2563eb',
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
    icon: 'checkmark.circle.fill',
  },
  preparing: {
    label: 'Собирается',
    color: '#7c3aed',
    backgroundColor: 'rgba(124, 58, 237, 0.12)',
    icon: 'bag.fill',
  },
  ready: {
    label: 'Готов',
    color: '#059669',
    backgroundColor: 'rgba(5, 150, 105, 0.12)',
    icon: 'checkmark.circle.fill',
  },
  delivering: {
    label: 'В пути',
    color: '#2563eb',
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
    icon: 'location.fill',
  },
  completed: {
    label: 'Доставлен',
    color: '#059669',
    backgroundColor: 'rgba(5, 150, 105, 0.12)',
    icon: 'checkmark.circle.fill',
  },
  cancelled: {
    label: 'Отменён',
    color: '#6b7280',
    backgroundColor: 'rgba(107, 114, 128, 0.12)',
    icon: 'xmark.circle.fill',
  },
  refunded: {
    label: 'Возврат',
    color: '#dc2626',
    backgroundColor: 'rgba(220, 38, 38, 0.12)',
    icon: 'arrow.counterclockwise',
  },
};

const getStatusConfig = (status: string) => {
  return (
    STATUS_CONFIG[status] ?? {
      label: status,
      color: '#6b7280',
      backgroundColor: 'rgba(107, 114, 128, 0.12)',
      icon: 'info.circle.fill',
    }
  );
};

const formatDate = (value: string) => {
  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatPrice = (value: string) => {
  const number = Number(value);

  if (Number.isNaN(number)) {
    return `${value} ₽`;
  }

  return `${number.toLocaleString('ru-RU', {
    minimumFractionDigits: number % 1 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  })} ₽`;
};

const formatProductsCount = (count: number) => {
  const lastTwoDigits = count % 100;
  const lastDigit = count % 10;

  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
    return `${count} товаров`;
  }

  if (lastDigit === 1) {
    return `${count} товар`;
  }

  if (lastDigit >= 2 && lastDigit <= 4) {
    return `${count} товара`;
  }

  return `${count} товаров`;
};

export default function OrdersScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();

  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const {
    user,
    isLoading: authLoading,
  } = useAuth();

  const [filter, setFilter] =
    useState<OrderFilter>('active');

  const [orders, setOrders] = useState<ApiOrder[]>([]);

  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(false);

  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] =
    useState(false);

  const [error, setError] =
    useState<string | null>(null);

  const loadOrders = useCallback(
    async (
      pageToLoad = 1,
      append = false,
    ) => {
      if (!user?.token) {
        return;
      }

      if (append) {
        setLoadingMore(true);
      } else {
        setLoading(true);
      }

      setError(null);

      try {
        const response =
          await ApiService.getOrders(
            user.token,
            pageToLoad,
            20,
          );

        setOrders((currentOrders) =>
          append
            ? [...currentOrders, ...response.data]
            : response.data,
        );

        setPage(response.paginator.current_page);
        setHasMore(response.paginator.has_more);
      } catch (loadError) {
        console.error(
          'Orders: failed to load orders',
          loadError,
        );

        setError(
          'Не удалось загрузить заказы. Попробуйте ещё раз.',
        );
      } finally {
        if (append) {
          setLoadingMore(false);
        } else {
          setLoading(false);
        }
      }
    },
    [user?.token],
  );

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!user?.token) {
      setOrders([]);
      setLoading(false);
      return;
    }

    loadOrders(1);
  }, [
    authLoading,
    user?.token,
    loadOrders,
  ]);

  const filteredOrders = useMemo(() => {
    return orders.filter((order) => {
      const isActive =
        ACTIVE_STATUSES.has(order.status);

      return filter === 'active'
        ? isActive
        : !isActive;
    });
  }, [orders, filter]);

  const loadMore = async () => {
    if (
      loadingMore ||
      loading ||
      !hasMore
    ) {
      return;
    }

    await loadOrders(page + 1, true);
  };

  return (
    <ThemedView
      style={[
        styles.container,
        { paddingTop: insets.top },
      ]}
    >
      <Stack.Screen
        options={{ headerShown: false }}
      />

      <View
        style={[
          styles.header,
          {
            borderBottomColor: colors.border,
          },
        ]}
      >
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
        >
          <IconSymbol
            name="chevron.left"
            size={24}
            color={colors.text}
          />
        </TouchableOpacity>

        <ThemedText style={styles.headerTitle}>
          История заказов
        </ThemedText>

        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        contentContainerStyle={
          styles.scrollContent
        }
        showsVerticalScrollIndicator={false}
        onScroll={({ nativeEvent }) => {
          const isCloseToBottom =
            nativeEvent.layoutMeasurement.height +
              nativeEvent.contentOffset.y >=
            nativeEvent.contentSize.height - 40;

          if (
            isCloseToBottom &&
            hasMore &&
            !loadingMore
          ) {
            loadMore();
          }
        }}
        scrollEventThrottle={400}
      >
        <View style={styles.filterContainer}>
          <View
            style={[
              styles.filterBg,
              {
                backgroundColor:
                  colorScheme === 'dark'
                    ? '#1a3825'
                    : '#e5e7eb',
              },
            ]}
          >
            <TouchableOpacity
              onPress={() =>
                setFilter('active')
              }
              style={[
                styles.filterItem,
                filter === 'active' && [
                  styles.filterItemSelected,
                  {
                    backgroundColor:
                      colorScheme === 'dark'
                        ? '#2d4f38'
                        : colors.surface,
                  },
                ],
              ]}
            >
              <ThemedText
                style={[
                  styles.filterText,
                  filter === 'active'
                    ? styles.filterTextActive
                    : {
                        color: colors.textSub,
                      },
                ]}
              >
                Активные
              </ThemedText>
            </TouchableOpacity>

            <TouchableOpacity
              onPress={() =>
                setFilter('history')
              }
              style={[
                styles.filterItem,
                filter === 'history' && [
                  styles.filterItemSelected,
                  {
                    backgroundColor:
                      colorScheme === 'dark'
                        ? '#2d4f38'
                        : colors.surface,
                  },
                ],
              ]}
            >
              <ThemedText
                style={[
                  styles.filterText,
                  filter === 'history'
                    ? styles.filterTextActive
                    : {
                        color: colors.textSub,
                      },
                ]}
              >
                История
              </ThemedText>
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.listContainer}>
          {loading ? (
            <View style={styles.loaderContainer}>
              <ActivityIndicator
                size="small"
                color={colors.primaryDark}
              />

              <ThemedText
                style={[
                  styles.loaderText,
                  { color: colors.textSub },
                ]}
              >
                Загружаем заказы...
              </ThemedText>
            </View>
          ) : error ? (
            <View style={styles.emptyContainer}>
              <ThemedText
                style={[
                  styles.errorText,
                  { color: colors.textSub },
                ]}
              >
                {error}
              </ThemedText>

              <TouchableOpacity
                style={[
                  styles.retryButton,
                  {
                    borderColor:
                      colors.primaryDark,
                  },
                ]}
                onPress={() => loadOrders(1)}
              >
                <ThemedText
                  style={[
                    styles.retryText,
                    {
                      color:
                        colors.primaryDark,
                    },
                  ]}
                >
                  Попробовать снова
                </ThemedText>
              </TouchableOpacity>
            </View>
          ) : filteredOrders.length > 0 ? (
            filteredOrders.map((order) => {
              const status =
                getStatusConfig(order.status);

              const summary =
                order.order_summary;

              const isCancelled =
                order.status === 'cancelled';

              return (
                <View
                  key={order.id}
                  style={[
                    styles.card,
                    {
                      backgroundColor:
                        colors.surface,
                      borderColor:
                        colors.border,
                      opacity: isCancelled
                        ? 0.75
                        : 1,
                    },
                  ]}
                >
                  <View
                    style={styles.cardHeader}
                  >
                    <View
                      style={styles.shopInfo}
                    >
                      <View
                        style={[
                          styles.shopIcon,
                          {
                            backgroundColor:
                              status.backgroundColor,
                          },
                        ]}
                      >
                        <IconSymbol
                          name={
                            status.icon as any
                          }
                          size={20}
                          color={status.color}
                        />
                      </View>

                      <View
                        style={
                          styles.headerOrderInfo
                        }
                      >
                        <ThemedText
                          style={styles.shopName}
                        >
                          Домашний гастроном
                        </ThemedText>

                        <ThemedText
                          style={[
                            styles.orderMeta,
                            {
                              color:
                                colors.textSub,
                            },
                          ]}
                        >
                          #{order.id} •{' '}
                          {formatDate(
                            order.created_at,
                          )}
                        </ThemedText>
                      </View>
                    </View>

                    <View
                      style={[
                        styles.statusBadge,
                        {
                          backgroundColor:
                            status.backgroundColor,
                        },
                      ]}
                    >
                      <ThemedText
                        style={[
                          styles.statusText,
                          {
                            color:
                              status.color,
                          },
                        ]}
                      >
                        {status.label}
                      </ThemedText>
                    </View>
                  </View>

                  <View
                    style={styles.cardBody}
                  >
                    {summary?.image ? (
                      <Image
                        source={{
                          uri: summary.image,
                        }}
                        style={
                          styles.productImage
                        }
                      />
                    ) : (
                      <View
                        style={[
                          styles.productPlaceholder,
                          {
                            backgroundColor:
                              colorScheme ===
                              'dark'
                                ? 'rgba(255,255,255,0.05)'
                                : '#f3f4f6',
                          },
                        ]}
                      >
                        <IconSymbol
                          name="bag.fill"
                          size={30}
                          color={
                            colors.textSub
                          }
                        />
                      </View>
                    )}

                    <View
                      style={styles.productInfo}
                    >
                      <ThemedText
                        numberOfLines={2}
                        style={styles.itemsText}
                      >
                        {summary?.product_names ||
                          'Состав заказа недоступен'}
                      </ThemedText>

                      <ThemedText
                        style={[
                          styles.itemsCount,
                          {
                            color:
                              colors.textSub,
                          },
                        ]}
                      >
                        {formatProductsCount(
                          summary?.items_count ??
                            0,
                        )}
                      </ThemedText>

                      <ThemedText
                        style={styles.priceText}
                      >
                        {formatPrice(
                          summary?.total_price ??
                            order.total_amount,
                        )}
                      </ThemedText>
                    </View>
                  </View>

                  <View
                    style={[
                      styles.divider,
                      {
                        backgroundColor:
                          colors.border,
                      },
                    ]}
                  />

                  <TouchableOpacity
                    onPress={() =>
                      router.push(
                        `/order/${order.id}`,
                      )
                    }
                    style={[
                      styles.actionButton,
                      {
                        backgroundColor:
                          colorScheme === 'dark'
                            ? 'rgba(255,255,255,0.05)'
                            : colors.background,
                      },
                    ]}
                  >
                    <IconSymbol
                      name="info.circle.fill"
                      size={18}
                      color={colors.text}
                    />

                    <ThemedText
                      style={styles.actionText}
                    >
                      Подробнее
                    </ThemedText>
                  </TouchableOpacity>
                </View>
              );
            })
          ) : (
            <View
              style={styles.emptyContainer}
            >
              <IconSymbol
                name="bag.fill"
                size={36}
                color={colors.textSub}
              />

              <ThemedText
                style={[
                  styles.emptyText,
                  { color: colors.textSub },
                ]}
              >
                {filter === 'active'
                  ? 'У вас нет активных заказов'
                  : 'История заказов пока пуста'}
              </ThemedText>
            </View>
          )}

          {loadingMore && (
            <View
              style={styles.loaderContainer}
            >
              <ActivityIndicator
                size="small"
                color={colors.primaryDark}
              />

              <ThemedText
                style={[
                  styles.loaderText,
                  {
                    color: colors.textSub,
                  },
                ]}
              >
                Загрузка...
              </ThemedText>
            </View>
          )}

          {!loading &&
            !loadingMore &&
            hasMore && (
              <TouchableOpacity
                onPress={loadMore}
                style={
                  styles.loadMoreButton
                }
              >
                <ThemedText
                  style={[
                    styles.loadMoreText,
                    {
                      color:
                        colors.primaryDark,
                    },
                  ]}
                >
                  Показать ещё
                </ThemedText>
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
    shadowOffset: {
      width: 0,
      height: 1,
    },
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
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },

  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
    gap: 8,
  },

  shopInfo: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },

  headerOrderInfo: {
    flex: 1,
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

  productPlaceholder: {
    width: 80,
    height: 80,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
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
    gap: 12,
  },

  emptyText: {
    fontSize: 14,
    textAlign: 'center',
  },

  errorText: {
    fontSize: 14,
    textAlign: 'center',
  },

  retryButton: {
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 10,
    borderWidth: 1,
  },

  retryText: {
    fontSize: 14,
    fontWeight: '600',
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
  },
});