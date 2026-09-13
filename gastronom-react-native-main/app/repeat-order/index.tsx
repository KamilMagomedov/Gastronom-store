import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
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

import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';
import {
  ApiError,
  ApiOrderRepeatCheck,
  ApiService,
} from '@/services/api';

export default function RepeatOrderScreen() {
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

  const [repeatCheck, setRepeatCheck] =
    useState<ApiOrderRepeatCheck | null>(null);

  const [loading, setLoading] = useState(true);
  const [isRepeating, setIsRepeating] =
    useState(false);

  const [error, setError] =
    useState<string | null>(null);

  useEffect(() => {
    if (authLoading) {
      return;
    }

    if (!orderId || !user?.token) {
      setError(
        'Не удалось определить заказ.',
      );
      setLoading(false);
      return;
    }

    let mounted = true;

    const checkOrder = async () => {
      setLoading(true);
      setError(null);

      try {
        const response =
          await ApiService.checkRepeatOrder(
            orderId,
            user.token,
          );

        if (mounted) {
          setRepeatCheck(response.data);
        }
      } catch (checkError) {
        console.error(
          'Repeat order: failed to check order',
          checkError,
        );

        const apiError =
          checkError as ApiError;

        if (mounted) {
          setError(
            apiError?.message ||
              'Не удалось проверить заказ.',
          );
        }
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    checkOrder();

    return () => {
      mounted = false;
    };
  }, [
    authLoading,
    orderId,
    user?.token,
  ]);

  const formatPrice = (
    value: number | string,
  ) => {
    const price = Number(value);

    if (Number.isNaN(price)) {
      return '—';
    }

    return `${price.toLocaleString(
      'ru-RU',
      {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      },
    )} ₽`;
  };

  const productsSubtotal =
    repeatCheck?.available_items.reduce(
      (sum, item) =>
        sum +
        Number(item.price) *
          item.requested_quantity,
      0,
    ) ?? 0;

  const handleRepeatOrder = async () => {
    if (
      !orderId ||
      !user?.token ||
      !repeatCheck?.can_repeat ||
      isRepeating
    ) {
      return;
    }

    setIsRepeating(true);
    setError(null);

    try {
      const response =
        await ApiService.repeatOrder(
          orderId,
          user.token,
        );

      router.replace({
        pathname: '/order/[id]',
        params: {
          id: String(response.data.id),
        },
      });
    } catch (repeatError) {
      console.error(
        'Repeat order: failed to repeat order',
        repeatError,
      );

      const apiError =
        repeatError as ApiError;

      setError(
        apiError?.message ||
          'Не удалось повторить заказ.',
      );
    } finally {
      setIsRepeating(false);
    }
  };

  return (
    <ThemedView
      style={[
        styles.container,
        {
          paddingTop: insets.top,
          backgroundColor:
            colors.background,
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
            borderBottomColor:
              colors.border,
            backgroundColor:
              colors.background,
          },
        ]}
      >
        <TouchableOpacity
          style={styles.backButton}
          onPress={() => router.back()}
        >
          <IconSymbol
            name="chevron.left"
            size={28}
            color={colors.text}
          />
        </TouchableOpacity>

        <ThemedText
          style={styles.headerTitle}
        >
          Повторить заказ
        </ThemedText>

        <View style={{ width: 40 }} />
      </View>

      {loading ? (
        <View style={styles.stateContainer}>
          <ActivityIndicator
            size="large"
            color="#13ec5b"
          />

          <ThemedText
            style={[
              styles.stateText,
              { color: colors.textSub },
            ]}
          >
            Проверяем товары...
          </ThemedText>
        </View>
      ) : error && !repeatCheck ? (
        <View style={styles.stateContainer}>
          <ThemedText
            style={styles.errorText}
          >
            {error}
          </ThemedText>

          <TouchableOpacity
            style={styles.repeatButton}
            onPress={() => router.back()}
          >
            <ThemedText
              style={
                styles.repeatButtonText
              }
            >
              Вернуться назад
            </ThemedText>
          </TouchableOpacity>
        </View>
      ) : repeatCheck ? (
        <ScrollView
          contentContainerStyle={[
            styles.scrollContent,
            {
              paddingBottom: Math.max(
                insets.bottom,
                24,
              ),
            },
          ]}
          showsVerticalScrollIndicator={
            false
          }
        >
          <View style={styles.content}>
            <View
              style={[
                styles.orderBrief,
                {
                  backgroundColor:
                    colors.surface,
                  borderColor:
                    colors.border,
                },
              ]}
            >
              <View
                style={[
                  styles.briefIconContainer,
                  {
                    backgroundColor:
                      colorScheme === 'dark'
                        ? colors.background
                        : '#e0fdf0',
                    borderColor:
                      'rgba(19, 236, 91, 0.2)',
                  },
                ]}
              >
                <IconSymbol
                  name="bag.fill"
                  size={24}
                  color={
                    colorScheme === 'dark'
                      ? '#13ec5b'
                      : '#0fb847'
                  }
                />
              </View>

              <View
                style={styles.briefInfo}
              >
                <ThemedText
                  style={styles.briefTitle}
                >
                  Заказ #{repeatCheck.order_id}
                </ThemedText>

                <ThemedText
                  style={[
                    styles.briefSubtitle,
                    {
                      color: repeatCheck.can_repeat
                        ? '#059669'
                        : '#dc2626',
                    },
                  ]}
                >
                  {repeatCheck.can_repeat
                    ? 'Все товары доступны'
                    : `Недоступно товаров: ${repeatCheck.total_unavailable_items}`}
                </ThemedText>
              </View>
            </View>

            <View
              style={styles.sectionHeader}
            >
              <ThemedText
                style={styles.sectionTitle}
              >
                Доступные товары
              </ThemedText>

              <ThemedText
                style={[
                  styles.itemCount,
                  {
                    color: colors.textSub,
                  },
                ]}
              >
                {
                  repeatCheck.total_available_items
                }
              </ThemedText>
            </View>

            <View
              style={[
                styles.itemsContainer,
                {
                  backgroundColor:
                    colors.surface,
                  borderColor:
                    colors.border,
                },
              ]}
            >
              {repeatCheck.available_items.map(
                (item, index) => (
                  <View
                    key={item.id}
                    style={[
                      styles.itemRow,
                      index <
                        repeatCheck
                          .available_items
                          .length -
                          1 && {
                        borderBottomColor:
                          colors.border,
                        borderBottomWidth: 1,
                      },
                    ]}
                  >
                    <View
                      style={[
                        styles.itemIconBox,
                        {
                          backgroundColor:
                            colorScheme ===
                            'dark'
                              ? colors.background
                              : '#f0f4f2',
                        },
                      ]}
                    >
                      <IconSymbol
                        name="bag.fill"
                        size={24}
                        color="#0fb847"
                      />
                    </View>

                    <View
                      style={
                        styles.itemMainInfo
                      }
                    >
                      <ThemedText
                        style={
                          styles.itemName
                        }
                      >
                        {item.product_name}
                      </ThemedText>

                      <ThemedText
                        style={[
                          styles.itemSpecs,
                          {
                            color:
                              colors.textSub,
                          },
                        ]}
                      >
                        {
                          item.requested_quantity
                        }{' '}
                        ×{' '}
                        {formatPrice(
                          item.price,
                        )}{' '}
                        / {item.unit}
                      </ThemedText>
                    </View>

                    <ThemedText
                      style={styles.itemPrice}
                    >
                      {formatPrice(
                        Number(item.price) *
                          item.requested_quantity,
                      )}
                    </ThemedText>
                  </View>
                ),
              )}
            </View>

            {repeatCheck.unavailable_items
              .length > 0 && (
              <>
                <View
                  style={
                    styles.sectionHeader
                  }
                >
                  <ThemedText
                    style={
                      styles.sectionTitle
                    }
                  >
                    Недоступные товары
                  </ThemedText>

                  <ThemedText
                    style={[
                      styles.itemCount,
                      { color: '#dc2626' },
                    ]}
                  >
                    {
                      repeatCheck
                        .total_unavailable_items
                    }
                  </ThemedText>
                </View>

                <View
                  style={[
                    styles.itemsContainer,
                    {
                      backgroundColor:
                        colors.surface,
                      borderColor:
                        colors.border,
                    },
                  ]}
                >
                  {repeatCheck.unavailable_items.map(
                    (item, index) => (
                      <View
                        key={item.id}
                        style={[
                          styles.itemRow,
                          index <
                            repeatCheck
                              .unavailable_items
                              .length -
                              1 && {
                            borderBottomColor:
                              colors.border,
                            borderBottomWidth: 1,
                          },
                        ]}
                      >
                        <View
                          style={[
                            styles.itemIconBox,
                            {
                              backgroundColor:
                                colorScheme ===
                                'dark'
                                  ? colors.background
                                  : '#fef2f2',
                            },
                          ]}
                        >
                          <IconSymbol
                            name="xmark"
                            size={22}
                            color="#dc2626"
                          />
                        </View>

                        <View
                          style={
                            styles.itemMainInfo
                          }
                        >
                          <ThemedText
                            style={
                              styles.itemName
                            }
                          >
                            {
                              item.product_name
                            }
                          </ThemedText>

                          <ThemedText
                            style={[
                              styles.itemSpecs,
                              {
                                color:
                                  '#dc2626',
                              },
                            ]}
                          >
                            Недоступно для
                            повторного заказа
                          </ThemedText>
                        </View>
                      </View>
                    ),
                  )}
                </View>
              </>
            )}

            <View
              style={[
                styles.summaryCard,
                {
                  backgroundColor:
                    colors.surface,
                  borderColor:
                    colors.border,
                },
              ]}
            >
              <View
                style={styles.summaryRow}
              >
                <ThemedText
                  style={[
                    styles.summaryLabel,
                    {
                      color: colors.textSub,
                    },
                  ]}
                >
                  Товары по актуальным ценам
                </ThemedText>

                <ThemedText
                  style={styles.summaryValue}
                >
                  {formatPrice(
                    productsSubtotal,
                  )}
                </ThemedText>
              </View>

              <ThemedText
                style={[
                  styles.infoText,
                  { color: colors.textSub },
                ]}
              >
                Стоимость доставки и итоговая
                сумма будут рассчитаны при
                создании нового заказа.
              </ThemedText>
            </View>

            {error ? (
              <ThemedText
                style={styles.errorText}
              >
                {error}
              </ThemedText>
            ) : null}

            <TouchableOpacity
              style={[
                styles.repeatButton,
                (!repeatCheck.can_repeat ||
                  isRepeating) && {
                  opacity: 0.5,
                },
              ]}
              activeOpacity={0.8}
              disabled={
                !repeatCheck.can_repeat ||
                isRepeating
              }
              onPress={handleRepeatOrder}
            >
              {isRepeating ? (
                <ActivityIndicator
                  size="small"
                  color="#102216"
                />
              ) : (
                <ThemedText
                  style={
                    styles.repeatButtonText
                  }
                >
                  {repeatCheck.can_repeat
                    ? 'Повторить заказ'
                    : 'Повторить заказ невозможно'}
                </ThemedText>
              )}
            </TouchableOpacity>
          </View>
        </ScrollView>
      ) : null}
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
  stateContainer: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
    gap: 12,
  },
  stateText: {
    fontSize: 14,
    fontWeight: '500',
  },
  errorText: {
    color: '#dc2626',
    fontSize: 14,
    lineHeight: 20,
    textAlign: 'center',
  },
  infoText: {
    fontSize: 13,
    lineHeight: 19,
  },
});
