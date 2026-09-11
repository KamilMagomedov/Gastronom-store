import React, { useEffect, useState } from 'react';

import {
  ActivityIndicator,
  Image,
  ScrollView,
  StyleSheet,
  TouchableOpacity,
  View,
} from 'react-native';

import {
  Stack,
  useLocalSearchParams,
  useRouter,
} from 'expo-router';

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

const STATUS_CONFIG: Record<
  string,
  {
    label: string;
    color: string;
    backgroundColor: string;
  }
> = {
  pending: {
    label: 'Принят',
    color: '#d97706',
    backgroundColor: 'rgba(217, 119, 6, 0.12)',
  },
  confirmed: {
    label: 'Подтверждён',
    color: '#2563eb',
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
  },
  preparing: {
    label: 'Собирается',
    color: '#7c3aed',
    backgroundColor: 'rgba(124, 58, 237, 0.12)',
  },
  ready: {
    label: 'Готов',
    color: '#059669',
    backgroundColor: 'rgba(5, 150, 105, 0.12)',
  },
  delivering: {
    label: 'В пути',
    color: '#2563eb',
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
  },
  completed: {
    label: 'Доставлен',
    color: '#059669',
    backgroundColor: 'rgba(5, 150, 105, 0.12)',
  },
  cancelled: {
    label: 'Отменён',
    color: '#6b7280',
    backgroundColor: 'rgba(107, 114, 128, 0.12)',
  },
  refunded: {
    label: 'Возврат',
    color: '#dc2626',
    backgroundColor: 'rgba(220, 38, 38, 0.12)',
  },
};

const formatPrice = (value: string | number | null | undefined) => {
  const amount = Number(value ?? 0);

  return `${amount.toLocaleString('ru-RU', {
    minimumFractionDigits: amount % 1 === 0 ? 0 : 2,
    maximumFractionDigits: 2,
  })} ₽`;
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

export default function OrderDetailScreen() {
  const router = useRouter();

  const { id } = useLocalSearchParams<{
    id?: string | string[];
  }>();

  const orderId = Array.isArray(id) ? id[0] : id;

  const insets = useSafeAreaInsets();

  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const {
    user,
    isLoading: authLoading,
  } = useAuth();

  const [order, setOrder] =
    useState<ApiOrder | null>(null);

  const [loading, setLoading] = useState(true);

  const [error, setError] =
    useState<string | null>(null);

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!user?.token || !orderId) {
      setError('Не удалось определить заказ.');
      setLoading(false);
      return;
    }

    let mounted = true;

    const loadOrder = async () => {
      setLoading(true);
      setError(null);

      try {
        const response =
          await ApiService.getOrder(
            orderId,
            user.token,
          );

        if (mounted) {
          setOrder(response.data);
        }
      } catch (loadError) {
        console.error(
          'Order details: failed to load order',
          loadError,
        );

        if (mounted) {
          setError(
            'Не удалось загрузить данные заказа.',
          );
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    loadOrder();

    return () => {
      mounted = false;
    };
  }, [
    authLoading,
    orderId,
    user?.token,
  ]);

  if (loading) {
    return (
      <ThemedView
        style={[
          styles.container,
          {
            paddingTop: insets.top,
            backgroundColor: colors.background,
            alignItems: 'center',
            justifyContent: 'center',
          },
        ]}
      >
        <ActivityIndicator
          size="large"
          color={colors.primaryDark}
        />

        <ThemedText
          style={{
            marginTop: 12,
            color: colors.textSub,
          }}
        >
          Загружаем заказ...
        </ThemedText>
      </ThemedView>
    );
  }

  if (error || !order) {
    return (
      <ThemedView
        style={[
          styles.container,
          {
            paddingTop: insets.top,
            backgroundColor: colors.background,
          },
        ]}
      >
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
            Детали заказа
          </ThemedText>

          <View style={{ width: 40 }} />
        </View>

        <View
          style={{
            flex: 1,
            alignItems: 'center',
            justifyContent: 'center',
            padding: 24,
          }}
        >
          <ThemedText
            style={{
              textAlign: 'center',
              color: colors.textSub,
            }}
          >
            {error || 'Заказ не найден.'}
          </ThemedText>
        </View>
      </ThemedView>
    );
  }

  const status =
    STATUS_CONFIG[order.status] ?? {
      label: order.status,
      color: '#6b7280',
      backgroundColor:
        'rgba(107, 114, 128, 0.12)',
    };

  const products = order.products ?? [];

  const total = Number(order.total_amount ?? 0);

  const deliveryCost = Number(
    order.delivery_cost ??
      order.shipping_amount ??
      0,
  );

  const productsSubtotal =
    Math.max(0, total - deliveryCost);

  const canCancel = [
    'pending',
    'confirmed',
    'preparing',
  ].includes(order.status);

  return (
    <ThemedView
      style={[
        styles.container,
        {
          paddingTop: insets.top,
          backgroundColor: colors.background,
        },
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
            backgroundColor: colors.background,
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
          Детали заказа
        </ThemedText>

        <View style={{ width: 40 }} />
      </View>

      <ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          {
            paddingBottom: Math.max(
              insets.bottom,
              20,
            ),
          },
        ]}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.content}>
          <View style={styles.titleSection}>
            <ThemedText
              style={styles.orderNumber}
            >
              Заказ #{order.id}
            </ThemedText>

            <View style={styles.statusRow}>
              <View
                style={[
                  styles.statusBadge,
                  {
                    backgroundColor:
                      status.backgroundColor,
                  },
                ]}
              >
                <IconSymbol
                  name="info.circle.fill"
                  size={14}
                  color={status.color}
                />

                <ThemedText
                  style={[
                    styles.statusText,
                    {
                      color: status.color,
                    },
                  ]}
                >
                  {status.label}
                </ThemedText>
              </View>

              <ThemedText
                style={[
                  styles.orderDate,
                  {
                    color: colors.textSub,
                  },
                ]}
              >
                {formatDate(order.created_at)}
              </ThemedText>
            </View>
          </View>

          <View style={styles.section}>
            <ThemedText
              style={styles.sectionTitle}
            >
              Товары ({products.length})
            </ThemedText>

            <View style={styles.itemsList}>
              {products.map((product) => (
                <View
                  key={product.id}
                  style={[
                    styles.itemCard,
                    {
                      backgroundColor:
                        colors.surface,
                      borderColor:
                        colors.border,
                    },
                  ]}
                >
                  {product.image ? (
                    <Image
                      source={{
                        uri: product.image,
                      }}
                      style={styles.itemImage}
                    />
                  ) : (
                    <View
                      style={[
                        styles.itemImage,
                        {
                          alignItems: 'center',
                          justifyContent:
                            'center',
                        },
                      ]}
                    >
                      <IconSymbol
                        name="bag.fill"
                        size={24}
                        color={colors.textSub}
                      />
                    </View>
                  )}

                  <View style={styles.itemInfo}>
                    <ThemedText
                      style={styles.itemName}
                      numberOfLines={1}
                    >
                      {product.name}
                    </ThemedText>

                    <ThemedText
                      style={[
                        styles.itemDesc,
                        {
                          color:
                            colors.textSub,
                        },
                      ]}
                    >
                      {product.unit}
                    </ThemedText>
                  </View>

                  <ThemedText
                    style={styles.itemPrice}
                  >
                    {formatPrice(
                      product.price,
                    )}
                  </ThemedText>
                </View>
              ))}
            </View>
          </View>

          <View style={styles.section}>
            <ThemedText
              style={styles.sectionTitle}
            >
              Информация о доставке
            </ThemedText>

            <View
              style={[
                styles.deliveryCard,
                {
                  backgroundColor:
                    colors.surface,
                  borderColor: colors.border,
                },
              ]}
            >
              <View style={styles.deliveryRow}>
                <View
                  style={[
                    styles.deliveryIcon,
                    {
                      backgroundColor:
                        'rgba(19, 236, 91, 0.15)',
                    },
                  ]}
                >
                  <IconSymbol
                    name="house.fill"
                    size={20}
                    color="#059669"
                  />
                </View>

                <View
                  style={styles.deliveryInfo}
                >
                  <ThemedText
                    style={[
                      styles.deliveryLabel,
                      {
                        color:
                          colors.textSub,
                      },
                    ]}
                  >
                    АДРЕС ДОСТАВКИ
                  </ThemedText>

                  <ThemedText
                    style={
                      styles.deliveryValue
                    }
                  >
                    {order.delivery_address ||
                      'Адрес не указан'}
                  </ThemedText>
                </View>
              </View>

              <View
                style={[
                  styles.divider,
                  {
                    backgroundColor:
                      colors.border,
                    marginVertical: 16,
                  },
                ]}
              />

              <View style={styles.deliveryRow}>
                <View
                  style={[
                    styles.deliveryIcon,
                    {
                      backgroundColor:
                        'rgba(19, 236, 91, 0.15)',
                    },
                  ]}
                >
                  <IconSymbol
                    name="bag.fill"
                    size={20}
                    color="#059669"
                  />
                </View>

                <View
                  style={styles.deliveryInfo}
                >
                  <View style={styles.rowBetween}>
                    <View style={{ flex: 1 }}>
                      <ThemedText
                        style={[
                          styles.deliveryLabel,
                          {
                            color:
                              colors.textSub,
                          },
                        ]}
                      >
                        СПОСОБ
                      </ThemedText>

                      <ThemedText
                        style={
                          styles.deliveryValue
                        }
                      >
                        {order.delivery_method
                          ?.label ||
                          'Не указан'}
                      </ThemedText>
                    </View>

                    <View
                      style={[
                        styles.freeBadge,
                        {
                          backgroundColor:
                            'rgba(19, 236, 91, 0.15)',
                        },
                      ]}
                    >
                      <ThemedText
                        style={styles.freeText}
                      >
                        {deliveryCost === 0
                          ? 'Бесплатно'
                          : formatPrice(
                              deliveryCost,
                            )}
                      </ThemedText>
                    </View>
                  </View>
                </View>
              </View>
            </View>
          </View>

          <View
            style={[
              styles.summaryCard,
              {
                backgroundColor:
                  colors.surface,
                borderColor: colors.border,
              },
            ]}
          >
            <View style={styles.summaryRow}>
              <ThemedText
                style={[
                  styles.summaryLabel,
                  {
                    color: colors.textSub,
                  },
                ]}
              >
                Стоимость товаров
              </ThemedText>

              <ThemedText
                style={styles.summaryValue}
              >
                {formatPrice(
                  productsSubtotal,
                )}
              </ThemedText>
            </View>

            <View style={styles.summaryRow}>
              <ThemedText
                style={[
                  styles.summaryLabel,
                  {
                    color: colors.textSub,
                  },
                ]}
              >
                Доставка
              </ThemedText>

              <ThemedText
                style={[
                  styles.summaryValue,
                  {
                    color:
                      deliveryCost === 0
                        ? '#10b981'
                        : colors.text,
                  },
                ]}
              >
                {deliveryCost === 0
                  ? 'Бесплатно'
                  : formatPrice(
                      deliveryCost,
                    )}
              </ThemedText>
            </View>

            <View
              style={[
                styles.divider,
                {
                  backgroundColor:
                    colors.border,
                  marginVertical: 8,
                },
              ]}
            />

            <View style={styles.summaryRow}>
              <ThemedText
                style={styles.totalLabel}
              >
                Итого
              </ThemedText>

              <ThemedText
                style={styles.totalValue}
              >
                {formatPrice(total)}
              </ThemedText>
            </View>
          </View>

          <View style={styles.actionButtons}>
            <TouchableOpacity
              style={styles.repeatButton}
              onPress={() =>
                router.push({
                  pathname: '/repeat-order',
                  params: {
                    id: String(order.id),
                  },
                })
              }
            >
              <IconSymbol
                name="arrow.clockwise"
                size={20}
                color="#102216"
              />

              <ThemedText
                style={
                  styles.repeatButtonText
                }
              >
                Повторить заказ
              </ThemedText>
            </TouchableOpacity>

            {canCancel && (
              <TouchableOpacity
                style={[
                  styles.cancelButton,
                  {
                    backgroundColor:
                      colorScheme === 'dark'
                        ? 'rgba(239, 68, 68, 0.1)'
                        : '#fef2f2',
                  },
                ]}
                onPress={() =>
                  router.push({
                    pathname:
                      '/cancel-order',
                    params: {
                      id: String(
                        order.id,
                      ),
                    },
                  })
                }
              >
                <IconSymbol
                  name="xmark"
                  size={20}
                  color="#ef4444"
                />

                <ThemedText
                  style={
                    styles.cancelButtonText
                  }
                >
                  Отменить заказ
                </ThemedText>
              </TouchableOpacity>
            )}
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
