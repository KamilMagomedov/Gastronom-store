import React from 'react';
import { StyleSheet, ScrollView, View, TouchableOpacity, Dimensions } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

const { width } = Dimensions.get('window');

export default function TermsOfServiceScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const sections = [
    {
      id: 1,
      title: 'Общие положения',
      content: 'Добро пожаловать в наше приложение для доставки свежих продуктов. Настоящие Условия регулируют использование вами нашего сервиса, включая все функциональные возможности и интерфейсы. Мы оставляем за собой право вносить изменения в данные условия в любое время без предварительного уведомления.',
    },
    {
      id: 2,
      title: 'Регистрация аккаунта',
      content: 'Для использования полного функционала приложения необходимо создать учетную запись. Вы несете ответственность за конфиденциальность данных вашего аккаунта.',
      bullets: [
        'Вам должно быть не менее 18 лет.',
        'Вы обязаны предоставить достоверную информацию.',
      ]
    },
    {
      id: 3,
      title: 'Доставка и оплата',
      content: 'Мы стремимся доставлять заказы в указанные сроки, однако время доставки может варьироваться в зависимости от загруженности и погодных условий.',
      note: 'Важно: Оплата производится через безопасный шлюз. Мы не храним полные данные ваших банковских карт.'
    },
    {
      id: 4,
      title: 'Возврат товаров',
      content: 'Если качество доставленных продуктов вас не устраивает, вы можете оформить возврат в течение 24 часов с момента получения заказа. Пожалуйста, свяжитесь с нашей службой поддержки через раздел "Помощь".',
    },
    {
      id: 5,
      title: 'Конфиденциальность',
      content: 'Мы уважаем вашу приватность. Сбор и обработка персональных данных осуществляется в строгом соответствии с нашей Политикой конфиденциальности.',
      link: true
    }
  ];

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Top Navigation Bar */}
      <View style={[styles.header, { backgroundColor: colorScheme === 'light' ? colors.surface : colors.surface, borderBottomColor: colors.border }]}>
        <TouchableOpacity 
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Условия использования</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView 
        showsVerticalScrollIndicator={false} 
        contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 120 }]}
      >
        <View style={styles.maxContentWidth}>
          {/* Meta Info Card */}
          <View style={[styles.metaCard, { backgroundColor: colorScheme === 'light' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.05)', borderColor: 'rgba(19, 236, 91, 0.2)' }]}>
            <View style={styles.metaHeader}>
              <View style={[styles.infoIconBg, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]}>
                <IconSymbol name="info.circle.fill" size={20} color={colors.primaryDark} />
              </View>
              <ThemedText style={[styles.metaHeaderText, { color: colors.primaryDark }]}>Важная информация</ThemedText>
            </View>
            <ThemedText style={[styles.metaDescription, { color: colors.textSub }]}>
              Пожалуйста, внимательно ознакомьтесь с условиями перед использованием нашего сервиса доставки. Использование приложения означает ваше полное согласие с данными правилами.
            </ThemedText>
            <View style={[styles.metaFooter, { borderTopColor: 'rgba(19, 236, 91, 0.1)' }]}>
              <IconSymbol name="clock.fill" size={14} color={colors.textSub} />
              <ThemedText style={styles.metaFooterText}>Последнее обновление: 15 октября 2023</ThemedText>
            </View>
          </View>

          {/* Main Content Blocks */}
          <View style={[styles.contentBlock, { backgroundColor: colorScheme === 'light' ? colors.surface : colors.surface }]}>
            {sections.map((section, index) => (
              <React.Fragment key={section.id}>
                <View style={styles.section}>
                  <View style={styles.sectionTitleRow}>
                    <View style={[styles.sectionBadge, { backgroundColor: colors.primary }]}>
                      <ThemedText style={styles.sectionBadgeText}>{section.id}</ThemedText>
                    </View>
                    <ThemedText style={styles.sectionTitle}>{section.title}</ThemedText>
                  </View>
                  
                  <ThemedText style={[styles.sectionContent, { color: colors.textSub }]}>
                    {section.content}
                    {section.link && (
                      <ThemedText 
                        style={[styles.link, { color: colors.primary }]}
                        onPress={() => router.push('/privacy-policy')}
                      >
                        {' '}Политикой конфиденциальности
                      </ThemedText>
                    )}
                  </ThemedText>

                  {section.bullets && (
                    <View style={styles.bulletsList}>
                      {section.bullets.map((bullet, bIndex) => (
                        <View key={bIndex} style={styles.bulletItem}>
                          <IconSymbol name="checkmark.circle.fill" size={18} color={colors.primary} />
                          <ThemedText style={[styles.bulletText, { color: colors.textSub }]}>{bullet}</ThemedText>
                        </View>
                      ))}
                    </View>
                  )}

                  {section.note && (
                    <View style={[styles.noteBox, { backgroundColor: colors.background, borderLeftColor: colors.primary }]}>
                      <ThemedText style={styles.noteText}>{section.note}</ThemedText>
                    </View>
                  )}
                </View>
                {index < sections.length - 1 && (
                  <View style={[styles.divider, { backgroundColor: colors.background }]} />
                )}
              </React.Fragment>
            ))}
          </View>

          <ThemedText style={[styles.agreementNotice, { color: colors.textSub }]}>
            Нажимая кнопку ниже, вы подтверждаете, что прочитали и поняли все условия данного соглашения.
          </ThemedText>
        </View>
      </ScrollView>

      {/* Sticky Bottom Action Bar */}
      <View style={[
        styles.bottomBar, 
        { 
          backgroundColor: colorScheme === 'light' ? colors.surface : colors.surface,
          borderTopColor: colors.border,
          paddingBottom: insets.bottom + 16,
        }
      ]}>
        <View style={styles.bottomBarContent}>
          <TouchableOpacity 
            style={[styles.declineButton, { backgroundColor: colors.background, borderColor: colors.border }]}
            onPress={() => router.back()}
          >
            <ThemedText style={[styles.declineButtonText, { color: colors.textSub }]}>Отклонить</ThemedText>
          </TouchableOpacity>
          
          <TouchableOpacity 
            style={[styles.acceptButton, { backgroundColor: colors.primary }]}
            onPress={() => router.back()}
            activeOpacity={0.9}
          >
            <ThemedText style={styles.acceptButtonText}>Я принимаю условия</ThemedText>
            <IconSymbol name="arrow.right" size={18} color="#0a3818" />
          </TouchableOpacity>
        </View>
      </View>
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
    height: 56,
    borderBottomWidth: 1,
    zIndex: 10,
  },
  backButton: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 20,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    flex: 1,
    textAlign: 'center',
  },
  scrollContent: {
    padding: 16,
  },
  maxContentWidth: {
    maxWidth: 500,
    alignSelf: 'center',
    width: '100%',
  },
  metaCard: {
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    marginBottom: 24,
  },
  metaHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 8,
  },
  infoIconBg: {
    padding: 6,
    borderRadius: 12,
  },
  metaHeaderText: {
    fontSize: 14,
    fontWeight: '700',
  },
  metaDescription: {
    fontSize: 12,
    lineHeight: 18,
  },
  metaFooter: {
    marginTop: 12,
    pt: 12,
    borderTopWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingTop: 12,
  },
  metaFooterText: {
    fontSize: 12,
    fontWeight: '500',
  },
  contentBlock: {
    borderRadius: 24,
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 2,
    marginBottom: 20,
  },
  section: {
    paddingVertical: 4,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 12,
  },
  sectionBadge: {
    width: 28,
    height: 28,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sectionBadgeText: {
    color: '#102216',
    fontSize: 14,
    fontWeight: '800',
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
  },
  sectionContent: {
    fontSize: 14,
    lineHeight: 22,
    textAlign: 'justify',
  },
  divider: {
    height: 1,
    width: '100%',
    marginVertical: 20,
  },
  bulletsList: {
    marginTop: 12,
    gap: 8,
  },
  bulletItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  bulletText: {
    fontSize: 14,
    flex: 1,
  },
  noteBox: {
    marginTop: 16,
    padding: 12,
    borderRadius: 12,
    borderLeftWidth: 4,
  },
  noteText: {
    fontSize: 13,
    fontWeight: '600',
  },
  link: {
    fontWeight: '600',
  },
  agreementNotice: {
    fontSize: 11,
    textAlign: 'center',
    paddingHorizontal: 24,
    opacity: 0.6,
    marginBottom: 20,
  },
  bottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    borderTopWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 10,
  },
  bottomBarContent: {
    flexDirection: 'row',
    gap: 12,
    maxWidth: 500,
    alignSelf: 'center',
    width: '100%',
  },
  declineButton: {
    flex: 1,
    height: 52,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  declineButtonText: {
    fontSize: 14,
    fontWeight: '700',
  },
  acceptButton: {
    flex: 2,
    height: 52,
    borderRadius: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  acceptButtonText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0a3818',
  },
});
