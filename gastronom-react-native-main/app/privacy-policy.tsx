import React from 'react';
import { StyleSheet, ScrollView, View, TouchableOpacity } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function PrivacyPolicyScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.primary} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Политика конфиденциальности</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={[styles.scrollContent, { paddingBottom: insets.bottom + 100 }]}>
        <View style={styles.metaInfo}>
          <ThemedText style={styles.lastUpdated}>Последнее обновление: 24 октября 2023</ThemedText>
          <View style={[styles.titleUnderline, { backgroundColor: colors.primary }]} />
        </View>

        <ThemedText style={styles.introduction}>
          Мы серьезно относимся к вашей конфиденциальности. В этом документе описано, как наше приложение для доставки продуктов собирает, использует и защищает ваши данные.
        </ThemedText>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <View style={[styles.iconContainer, { backgroundColor: colorScheme === 'light' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.2)' }]}>
              <IconSymbol name="folder" size={20} color={colors.primary} />
            </View>
            <ThemedText style={styles.sectionTitle}>1. Сбор информации</ThemedText>
          </View>
          <ThemedText style={[styles.sectionText, { color: colors.text }]}>
            Мы собираем информацию, которую вы предоставляете нам напрямую, например, когда вы создаете учетную запись, делаете заказ или обращаетесь в нашу службу поддержки. Типы информации, которую мы можем собирать, включают:
          </ThemedText>
          <View style={styles.list}>
            {['Ваше имя и фамилия', 'Адрес электронной почты и номер телефона', 'Адрес доставки и платежная информация'].map((item, index) => (
              <View key={index} style={styles.listItem}>
                <View style={[styles.bullet, { backgroundColor: colors.primary }]} />
                <ThemedText style={styles.listItemText}>{item}</ThemedText>
              </View>
            ))}
          </View>
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <View style={[styles.iconContainer, { backgroundColor: colorScheme === 'light' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.2)' }]}>
              <IconSymbol name="chart.bar.fill" size={20} color={colors.primary} />
            </View>
            <ThemedText style={styles.sectionTitle}>2. Использование информации</ThemedText>
          </View>
          <ThemedText style={[styles.sectionText, { color: colors.text }]}>
            Мы используем полученные данные для обеспечения качественного сервиса доставки продуктов. Это включает в себя обработку ваших заказов, отправку уведомлений о статусе доставки, персонализацию предложений и улучшение функциональности нашего приложения.
          </ThemedText>
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <View style={[styles.iconContainer, { backgroundColor: colorScheme === 'light' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.2)' }]}>
              <IconSymbol name="shield.fill" size={20} color={colors.primary} />
            </View>
            <ThemedText style={styles.sectionTitle}>3. Безопасность данных</ThemedText>
          </View>
          <ThemedText style={[styles.sectionText, { color: colors.text }]}>
            Мы принимаем все необходимые технические и организационные меры для защиты вашей персональной информации от несанкционированного доступа, изменения, раскрытия или уничтожения. Все платежные транзакции защищены шифрованием.
          </ThemedText>
        </View>

        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <View style={[styles.iconContainer, { backgroundColor: colorScheme === 'light' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.2)' }]}>
              <IconSymbol name="gavel" size={20} color={colors.primary} />
            </View>
            <ThemedText style={styles.sectionTitle}>4. Ваши права</ThemedText>
          </View>
          <ThemedText style={[styles.sectionText, { color: colors.text }]}>
            Вы имеете право запросить копию ваших данных, исправить неточности или потребовать удаления вашего аккаунта. Для реализации этих прав свяжитесь с нами через раздел поддержки в приложении.
          </ThemedText>
        </View>

        <View style={[styles.contactCard, { backgroundColor: colorScheme === 'light' ? '#f9fafb' : 'rgba(255, 255, 255, 0.05)' }]}>
          <ThemedText style={styles.contactTitle}>Есть вопросы?</ThemedText>
          <ThemedText style={styles.contactText}>
            Свяжитесь с нами по адресу:{'\n'}
            <ThemedText style={[styles.email, { color: colors.primary }]}>privacy@groceryapp.ru</ThemedText>
          </ThemedText>
        </View>
      </ScrollView>

      {/* Sticky Bottom Action Bar */}
      <View style={[
        styles.bottomBar, 
        { 
          backgroundColor: colorScheme === 'light' ? colors.background : colors.background,
          borderTopColor: colors.border,
          paddingBottom: insets.bottom + 16,
        }
      ]}>
        <TouchableOpacity 
          style={[styles.acceptButton, { backgroundColor: colors.primary }]}
          onPress={() => router.back()}
          activeOpacity={0.9}
        >
          <ThemedText style={styles.acceptButtonText}>Принимаю условия</ThemedText>
        </TouchableOpacity>
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
  },
  backButton: {
    width: 40,
    height: 40,
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
    padding: 20,
  },
  metaInfo: {
    marginBottom: 24,
  },
  lastUpdated: {
    fontSize: 14,
    fontWeight: '500',
    color: '#6b7280',
    marginBottom: 4,
  },
  titleUnderline: {
    height: 4,
    width: 48,
    borderRadius: 2,
    opacity: 0.4,
  },
  introduction: {
    fontSize: 16,
    lineHeight: 24,
    opacity: 0.8,
    marginBottom: 32,
  },
  section: {
    marginBottom: 32,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  iconContainer: {
    width: 32,
    height: 32,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
  },
  sectionText: {
    fontSize: 16,
    lineHeight: 28,
  },
  list: {
    marginTop: 12,
    paddingLeft: 8,
  },
  listItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  bullet: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginTop: 10,
    marginRight: 8,
  },
  listItemText: {
    fontSize: 16,
    lineHeight: 24,
  },
  contactCard: {
    marginTop: 32,
    padding: 16,
    borderRadius: 12,
  },
  contactTitle: {
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 8,
  },
  contactText: {
    fontSize: 14,
    color: '#6b7280',
  },
  email: {
    fontWeight: '500',
  },
  bottomBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    borderTopWidth: 1,
  },
  acceptButton: {
    height: 56,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  acceptButtonText: {
    fontSize: 16,
    fontWeight: '700',
    color: '#0a3818',
  },
});
