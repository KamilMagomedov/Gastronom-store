import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ApiService, type ApiNotification } from '@/services/api';
import { useFocusEffect } from '@react-navigation/native';
import { Stack, useRouter } from 'expo-router';
import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

type NotificationType = 'Все' | 'Заказы' | 'Акции' | 'Новости';

type NotificationSection = 'Сегодня' | 'Вчера' | 'Ранее';

interface NotificationItem {
  id: string;
  title: string;
  message: string;
  time: string;
  type: 'order' | 'promo' | 'news';
  icon: string;
  iconBg: {
    light: string;
    dark: string;
  };
  iconColor: string;
  isRead: boolean;
  section: NotificationSection;
  opacity: number;
  orderId: number | null;
}

const CATEGORIES: NotificationType[] = ['Все', 'Заказы', 'Акции', 'Новости'];

const getNotificationSection = (createdAt: string | null): NotificationSection => {
  if (!createdAt) {
    return 'Ранее';
  }

  const date = new Date(createdAt);

  if (Number.isNaN(date.getTime())) {
    return 'Ранее';
  }

  const now = new Date();

  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);

  if (date >= today) {
    return 'Сегодня';
  }

  if (date >= yesterday) {
    return 'Вчера';
  }

  return 'Ранее';
};

const getNotificationTime = (createdAt: string | null): string => {
  if (!createdAt) {
    return '';
  }

  const date = new Date(createdAt);

  if (Number.isNaN(date.getTime())) {
    return '';
  }

  const now = new Date();
  const difference = now.getTime() - date.getTime();

  const minutes = Math.floor(difference / (1000 * 60));

  if (minutes >= 0 && minutes < 1) {
    return 'только что';
  }

  if (minutes >= 1 && minutes < 60) {
    return `${minutes} мин`;
  }

  const hours = Math.floor(minutes / 60);

  if (hours >= 1 && hours < 24) {
    return `${hours} ч`;
  }

  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
  });
};

const getNotificationVisual = (item: ApiNotification) => {
  if (item.type === 'promo') {
    return {
      type: 'promo' as const,
      icon: 'percent',
      iconBg: {
        light: 'rgba(250, 204, 21, 0.1)',
        dark: 'rgba(250, 204, 21, 0.15)',
      },
      iconColor: '#ca8a04',
    };
  }

  if (item.type === 'news') {
    return {
      type: 'news' as const,
      icon: 'bell.fill',
      iconBg: {
        light: 'rgba(59, 130, 246, 0.1)',
        dark: 'rgba(59, 130, 246, 0.15)',
      },
      iconColor: '#2563eb',
    };
  }

  if (item.status === 'completed') {
    return {
      type: 'order' as const,
      icon: 'checkmark.circle.fill',
      iconBg: {
        light: 'rgba(74, 222, 128, 0.1)',
        dark: 'rgba(74, 222, 128, 0.15)',
      },
      iconColor: '#16a34a',
    };
  }

  if (item.status === 'delivering') {
    return {
      type: 'order' as const,
      icon: 'shippingbox.fill',
      iconBg: {
        light: 'rgba(19, 236, 91, 0.1)',
        dark: 'rgba(19, 236, 91, 0.15)',
      },
      iconColor: '#13ec5b',
    };
  }

  if (item.status === 'cancelled' || item.status === 'refunded') {
    return {
      type: 'order' as const,
      icon: 'xmark.circle.fill',
      iconBg: {
        light: 'rgba(239, 68, 68, 0.1)',
        dark: 'rgba(239, 68, 68, 0.15)',
      },
      iconColor: '#dc2626',
    };
  }

  return {
    type: 'order' as const,
    icon: 'clock.fill',
    iconBg: {
      light: 'rgba(59, 130, 246, 0.1)',
      dark: 'rgba(59, 130, 246, 0.15)',
    },
    iconColor: '#2563eb',
  };
};

export default function NotificationsScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { user, isLoading: authLoading } = useAuth();

  const [notifications, setNotifications] = useState<ApiNotification[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeCategory, setActiveCategory] = useState<NotificationType>('Все');

  const [updatingNotificationId, setUpdatingNotificationId] = useState<string | null>(null);
  const [markingAll, setMarkingAll] = useState(false);

  const handleBack = () => {
    if (router.canGoBack()) {
      router.back();
      return;
    }

    router.replace('/(tabs)/profile');
  };

  useFocusEffect(
    useCallback(() => {
      if (authLoading) {
        return;
      }

      if (!user?.token) {
        setNotifications([]);
        setLoading(false);
        return;
      }

      let mounted = true;

      const loadNotifications = async () => {
        try {
          setLoading(true);
          setError(null);

          const response = await ApiService.getNotifications(user.token);

          if (mounted) {
            setNotifications(response.data.notifications);
          }
        } catch (loadError) {
          console.error('Notifications: failed to load notifications', loadError);

          if (mounted) {
            setError('Не удалось загрузить уведомления.');
          }
        } finally {
          if (mounted) {
            setLoading(false);
          }
        }
      };

      void loadNotifications();

      return () => {
        mounted = false;
      };
    }, [authLoading, user?.token]),
  );

  const notificationItems = useMemo<NotificationItem[]>(() => {
    return notifications.map((item) => {
      const visual = getNotificationVisual(item);

      return {
        id: item.id,
        title: item.title,
        message: item.message,
        time: getNotificationTime(item.created_at),
        type: visual.type,
        icon: visual.icon,
        iconBg: visual.iconBg,
        iconColor: visual.iconColor,
        isRead: item.read_at !== null,
        section: getNotificationSection(item.created_at),
        opacity: item.read_at !== null ? 0.85 : 1,
        orderId: item.order_id,
      };
    });
  }, [notifications]);

  const filteredNotifications = useMemo(() => {
    if (activeCategory === 'Все') {
      return notificationItems;
    }

    const categoryMap: Record<Exclude<NotificationType, 'Все'>, NotificationItem['type']> = {
      Заказы: 'order',
      Акции: 'promo',
      Новости: 'news',
    };

    return notificationItems.filter(
      (notification) => notification.type === categoryMap[activeCategory],
    );
  }, [activeCategory, notificationItems]);

  const sections = ['Сегодня', 'Вчера', 'Ранее'] as const;

  const handleNotificationPress = async (item: NotificationItem) => {
    if (!user?.token) {
      return;
    }

    if (!item.isRead) {
      try {
        setUpdatingNotificationId(item.id);

        const response = await ApiService.markNotificationAsRead(item.id, user.token);

        setNotifications((current) =>
          current.map((notification) =>
            notification.id === item.id
              ? {
                  ...notification,
                  read_at: response.data.read_at,
                }
              : notification,
          ),
        );
      } catch (readError) {
        console.error('Notifications: failed to mark notification as read', readError);
      } finally {
        setUpdatingNotificationId(null);
      }
    }

    if (item.type === 'order' && item.orderId !== null) {
      router.push(`/order/${item.orderId}`);
    }
  };

  const handleMarkAllAsRead = async () => {
    if (!user?.token || markingAll) {
      return;
    }

    const hasUnread = notifications.some((notification) => notification.read_at === null);

    if (!hasUnread) {
      return;
    }

    try {
      setMarkingAll(true);

      await ApiService.markAllNotificationsAsRead(user.token);

      const readAt = new Date().toISOString();

      setNotifications((current) =>
        current.map((notification) => ({
          ...notification,
          read_at: notification.read_at ?? readAt,
        })),
      );
    } catch (readError) {
      console.error('Notifications: failed to mark all as read', readError);
    } finally {
      setMarkingAll(false);
    }
  };

  const renderNotification = (item: NotificationItem) => (
    <TouchableOpacity
      key={item.id}
      activeOpacity={0.7}
      onPress={() => void handleNotificationPress(item)}
      disabled={updatingNotificationId === item.id}
      style={[
        styles.notificationCard,
        {
          backgroundColor: colors.surface,
          borderColor: colorScheme === 'light' ? 'transparent' : colors.border,
          opacity: item.opacity || 1,
        },
      ]}
    >
      {!item.isRead && <View style={[styles.unreadDot, { backgroundColor: colors.primary }]} />}
      <View style={styles.cardContent}>
        <View style={[styles.iconContainer, { backgroundColor: item.iconBg[colorScheme] }]}>
          <IconSymbol name={item.icon as any} size={24} color={item.iconColor} />
        </View>

        <View style={styles.textContainer}>
          <View style={styles.cardHeader}>
            <Text style={[styles.title, { color: colors.text }]} numberOfLines={1}>
              {item.title}
            </Text>
            <Text style={[styles.time, { color: colors.textSub }]}>{item.time}</Text>
          </View>
          <Text style={[styles.message, { color: colors.textSub }]} numberOfLines={2}>
            {item.message}
          </Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <ThemedView style={styles.container}>
      <Stack.Screen options={{ headerShown: false }} />

      {/* Top App Bar */}
      <View
        style={[
          styles.appBar,
          {
            paddingTop: insets.top + 12,
            backgroundColor: colors.background + 'E6',
            borderBottomColor: colors.border + '80',
          },
        ]}
      >
        <View style={styles.appBarContent}>
          <TouchableOpacity onPress={handleBack} style={styles.backButton}>
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Уведомления</Text>
          <TouchableOpacity
            activeOpacity={0.7}
            onPress={() => void handleMarkAllAsRead()}
            disabled={markingAll}
          >
            <Text
              style={[
                styles.readAllText,
                {
                  color: colors.primaryDark,
                  opacity: markingAll ? 0.5 : 1,
                },
              ]}
            >
              {markingAll ? 'Подождите...' : 'Все прочитано'}
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        stickyHeaderIndices={[0]}
        contentContainerStyle={{ paddingBottom: insets.bottom + 80 }}
      >
        {/* Chips / Tabs (Sticky) */}
        <View style={[styles.chipsContainer, { backgroundColor: colors.background }]}>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.chipsScroll}
          >
            {CATEGORIES.map((category) => {
              const isActive = activeCategory === category;
              return (
                <TouchableOpacity
                  key={category}
                  onPress={() => setActiveCategory(category)}
                  style={[
                    styles.chip,
                    isActive
                      ? { backgroundColor: colors.primary, borderColor: colors.primary }
                      : { backgroundColor: colors.surface, borderColor: colors.border },
                  ]}
                >
                  <Text
                    style={[
                      styles.chipText,
                      isActive
                        ? { color: '#000', fontWeight: '700' }
                        : { color: colors.text, fontWeight: '500' },
                    ]}
                  >
                    {category}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>

        <View style={styles.listContainer}>
          {loading ? (
            <View style={styles.emptyState}>
              <ActivityIndicator size="large" color={colors.primary} />

              <Text style={[styles.emptyText, { color: colors.textSub }]}>
                Загрузка уведомлений...
              </Text>
            </View>
          ) : error ? (
            <View style={styles.emptyState}>
              <IconSymbol name="bell.slash.fill" size={48} color={colors.textSub} />

              <Text style={[styles.emptyTitle, { color: colors.text }]}>
                Не удалось загрузить уведомления
              </Text>

              <Text style={[styles.emptyText, { color: colors.textSub }]}>{error}</Text>
            </View>
          ) : filteredNotifications.length > 0 ? (
            sections.map((section) => {
              const sectionItems = filteredNotifications.filter((n) => n.section === section);
              if (sectionItems.length === 0) return null;

              return (
                <View key={section} style={styles.sectionContainer}>
                  <Text style={[styles.sectionTitle, { color: colors.textSub }]}>
                    {section.toUpperCase()}
                  </Text>
                  <View style={styles.sectionGap}>{sectionItems.map(renderNotification)}</View>
                </View>
              );
            })
          ) : (
            <View style={styles.emptyState}>
              <View style={[styles.emptyIconContainer, { backgroundColor: colors.surface }]}>
                <IconSymbol name="bell.slash.fill" size={48} color={colors.textSub} />
              </View>
              <Text style={[styles.emptyTitle, { color: colors.text }]}>Нет уведомлений</Text>
              <Text style={[styles.emptyText, { color: colors.textSub }]}>
                В категории «{activeCategory}» пока ничего нет.
              </Text>
            </View>
          )}
        </View>
      </ScrollView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  appBar: {
    paddingHorizontal: 16,
    paddingBottom: 12,
    borderBottomWidth: 1,
    zIndex: 50,
  },
  appBarContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  backButton: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: -10,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: -0.5,
    flex: 1,
    textAlign: 'center',
    marginLeft: -10, // Adjust for back button
  },
  readAllText: {
    fontSize: 14,
    fontWeight: '700',
    minWidth: 100,
    textAlign: 'right',
  },
  chipsContainer: {
    paddingVertical: 12,
    zIndex: 40,
  },
  chipsScroll: {
    paddingHorizontal: 16,
    gap: 8,
  },
  chip: {
    height: 38,
    paddingHorizontal: 20,
    borderRadius: 19,
    justifyContent: 'center',
    borderWidth: 1,
  },
  chipText: {
    fontSize: 14,
  },
  listContainer: {
    paddingHorizontal: 16,
  },
  sectionContainer: {
    marginBottom: 24,
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '800',
    letterSpacing: 1.2,
    marginBottom: 12,
    paddingLeft: 4,
  },
  sectionGap: {
    gap: 12,
  },
  notificationCard: {
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
    position: 'relative',
  },
  unreadDot: {
    position: 'absolute',
    top: 14,
    right: 14,
    width: 10,
    height: 10,
    borderRadius: 5,
    zIndex: 1,
  },
  cardContent: {
    flexDirection: 'row',
    gap: 16,
  },
  iconContainer: {
    width: 48,
    height: 48,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  textContainer: {
    flex: 1,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'baseline',
    marginBottom: 4,
    paddingRight: 12,
  },
  title: {
    fontSize: 16,
    fontWeight: '700',
    flex: 1,
  },
  time: {
    fontSize: 12,
    fontWeight: '600',
    marginLeft: 8,
  },
  message: {
    fontSize: 14,
    lineHeight: 20,
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingTop: 60,
  },
  emptyIconContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 20,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '800',
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 15,
    textAlign: 'center',
    opacity: 0.7,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    fontSize: 16,
    fontWeight: '600',
  },
});
