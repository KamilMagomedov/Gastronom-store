import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { Stack, useRouter } from 'expo-router';
import React, { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

type NotificationType = 'Все' | 'Заказы' | 'Акции' | 'Новости';

interface Notification {
  id: string;
  title: string;
  message: string;
  time: string;
  type: 'order' | 'promo' | 'news';
  icon: string;
  iconBg: { light: string; dark: string };
  iconColor: string;
  isRead: boolean;
  section: 'Сегодня' | 'Вчера' | 'Ранее';
  opacity?: number;
}

const CATEGORIES: NotificationType[] = ['Все', 'Заказы', 'Акции', 'Новости'];

const NOTIFICATIONS: Notification[] = [
  {
    id: '1',
    title: 'Заказ №5932 доставлен',
    message: 'Курьер оставил заказ у двери. Приятного аппетита!',
    time: '10 мин',
    type: 'order',
    icon: 'checkmark.circle.fill',
    iconBg: { light: 'rgba(74, 222, 128, 0.1)', dark: 'rgba(74, 222, 128, 0.15)' },
    iconColor: '#16a34a',
    isRead: false,
    section: 'Сегодня'
  },
  {
    id: '2',
    title: 'Скидка 20% на фрукты',
    message: 'Только сегодня! Свежие яблоки и груши по специальной цене.',
    time: '2 ч',
    type: 'promo',
    icon: 'percent',
    iconBg: { light: 'rgba(250, 204, 21, 0.1)', dark: 'rgba(250, 204, 21, 0.15)' },
    iconColor: '#ca8a04',
    isRead: false,
    section: 'Сегодня'
  },
  {
    id: '3',
    title: 'Курьер уже в пути',
    message: 'Заказ №5934 передан курьеру. Ожидайте доставку через 25-30 минут.',
    time: '4 ч',
    type: 'order',
    icon: 'shippingbox.fill',
    iconBg: { light: 'rgba(19, 236, 91, 0.1)', dark: 'rgba(19, 236, 91, 0.15)' },
    iconColor: '#13ec5b',
    isRead: true,
    section: 'Сегодня'
  },
  {
    id: '4',
    title: 'Обновление приложения',
    message: 'Мы улучшили поиск товаров. Теперь находить любимые продукты проще.',
    time: '14:00',
    type: 'news',
    icon: 'arrow.up.circle.fill',
    iconBg: { light: 'rgba(59, 130, 246, 0.1)', dark: 'rgba(59, 130, 246, 0.15)' },
    iconColor: '#2563eb',
    isRead: true,
    section: 'Вчера'
  },
  {
    id: '5',
    title: 'Кэшбэк 5% по вашей карте',
    message: 'При оплате картой МИР возвращаем 5% бонусами на ваш счет.',
    time: '11:20',
    type: 'promo',
    icon: 'creditcard.fill',
    iconBg: { light: 'rgba(168, 85, 247, 0.1)', dark: 'rgba(168, 85, 247, 0.15)' },
    iconColor: '#9333ea',
    isRead: true,
    section: 'Вчера'
  },
  {
    id: '6',
    title: 'Заказ №5931 собран',
    message: 'Сборщик отобрал самые свежие продукты для вашего заказа.',
    time: '09:45',
    type: 'order',
    icon: 'bag.fill',
    iconBg: { light: 'rgba(229, 231, 235, 1)', dark: 'rgba(31, 41, 55, 1)' },
    iconColor: '#6b7280',
    isRead: true,
    section: 'Вчера',
    opacity: 0.8
  },
  {
    id: '7',
    title: 'Новая подборка рецептов',
    message: 'Приготовили для вас 5 идей быстрых ужинов из сезонных овощей.',
    time: '12 Окт',
    type: 'news',
    icon: 'book.fill',
    iconBg: { light: 'rgba(236, 72, 153, 0.1)', dark: 'rgba(236, 72, 153, 0.15)' },
    iconColor: '#db2777',
    isRead: true,
    section: 'Ранее'
  },
  {
    id: '8',
    title: 'Заказ №5928 отменен',
    message: 'Возврат средств за заказ №5928 произведен. Деньги вернутся в течение 3-х дней.',
    time: '10 Окт',
    type: 'order',
    icon: 'xmark.circle.fill',
    iconBg: { light: 'rgba(239, 68, 68, 0.1)', dark: 'rgba(239, 68, 68, 0.15)' },
    iconColor: '#dc2626',
    isRead: true,
    section: 'Ранее'
  },
  {
    id: '9',
    title: 'Подарки к выходным',
    message: 'Закажите на сумму от 2500 руб и получите набор ягод в подарок!',
    time: '08 Окт',
    type: 'promo',
    icon: 'gift.fill',
    iconBg: { light: 'rgba(249, 115, 22, 0.1)', dark: 'rgba(249, 115, 22, 0.15)' },
    iconColor: '#ea580c',
    isRead: true,
    section: 'Ранее'
  }
];

export default function NotificationsScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [activeCategory, setActiveCategory] = useState<NotificationType>('Все');

  const filteredNotifications = useMemo(() => {
    if (activeCategory === 'Все') return NOTIFICATIONS;
    
    const categoryMap: Record<Exclude<NotificationType, 'Все'>, string> = {
      'Заказы': 'order',
      'Акции': 'promo',
      'Новости': 'news'
    };
    
    return NOTIFICATIONS.filter(n => n.type === categoryMap[activeCategory as keyof typeof categoryMap]);
  }, [activeCategory]);

  const sections = ['Сегодня', 'Вчера', 'Ранее'] as const;

  const renderNotification = (item: Notification) => (
    <TouchableOpacity 
      key={item.id} 
      activeOpacity={0.7}
      style={[
        styles.notificationCard, 
        { 
          backgroundColor: colors.surface, 
          borderColor: colorScheme === 'light' ? 'transparent' : colors.border,
          opacity: item.opacity || 1
        }
      ]}
    >
      {!item.isRead && (
        <View style={[styles.unreadDot, { backgroundColor: colors.primary }]} />
      )}
      <View style={styles.cardContent}>
        <View style={[styles.iconContainer, { backgroundColor: item.iconBg[colorScheme] }]}>
          <IconSymbol name={item.icon as any} size={24} color={item.iconColor} />
        </View>
        
        <View style={styles.textContainer}>
          <View style={styles.cardHeader}>
            <Text style={[styles.title, { color: colors.text }]} numberOfLines={1}>{item.title}</Text>
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
      <View style={[styles.appBar, { paddingTop: insets.top + 12, backgroundColor: colors.background + 'E6', borderBottomColor: colors.border + '80' }]}>
        <View style={styles.appBarContent}>
          <TouchableOpacity 
            onPress={() => router.back()}
            style={styles.backButton}
          >
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <Text style={[styles.headerTitle, { color: colors.text }]}>Уведомления</Text>
          <TouchableOpacity activeOpacity={0.7}>
            <Text style={[styles.readAllText, { color: colors.primaryDark }]}>Все прочитано</Text>
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
                    isActive ? { backgroundColor: colors.primary, borderColor: colors.primary } : { backgroundColor: colors.surface, borderColor: colors.border }
                  ]}
                >
                  <Text style={[
                    styles.chipText,
                    isActive ? { color: '#000', fontWeight: '700' } : { color: colors.text, fontWeight: '500' }
                  ]}>
                    {category}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>

        <View style={styles.listContainer}>
          {filteredNotifications.length > 0 ? (
            sections.map(section => {
              const sectionItems = filteredNotifications.filter(n => n.section === section);
              if (sectionItems.length === 0) return null;

              return (
                <View key={section} style={styles.sectionContainer}>
                  <Text style={[styles.sectionTitle, { color: colors.textSub }]}>{section.toUpperCase()}</Text>
                  <View style={styles.sectionGap}>
                    {sectionItems.map(renderNotification)}
                  </View>
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
                В категории "{activeCategory}" пока ничего нет.
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
