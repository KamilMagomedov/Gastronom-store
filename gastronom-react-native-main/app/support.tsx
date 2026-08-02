
import React, { useState } from 'react';
import { StyleSheet, View, TouchableOpacity, ScrollView, TextInput, KeyboardAvoidingView, Platform, LayoutAnimation } from 'react-native';
import { useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

interface FAQItemProps {
  icon: string;
  question: string;
  answer: string;
}

function FAQItem({ icon, question, answer }: FAQItemProps) {
  const [expanded, setExpanded] = useState(false);
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const toggle = () => {
    LayoutAnimation.configureNext(LayoutAnimation.Presets.easeInEaseOut);
    setExpanded(!expanded);
  };

  return (
    <TouchableOpacity 
      style={[styles.faqItem, { backgroundColor: colors.surface, borderColor: colors.border }]} 
      onPress={toggle}
      activeOpacity={0.7}
    >
      <View>
        <View style={styles.faqHeader}>
          <View style={styles.faqItemLeft}>
            <View style={[styles.faqIconBox, { backgroundColor: colorScheme === 'dark' ? 'rgba(255,255,255,0.05)' : '#f3f4f6' }]}>
              <IconSymbol name={icon as any} size={20} color={colors.textSub} />
            </View>
            <ThemedText style={styles.faqItemText}>{question}</ThemedText>
          </View>
          <IconSymbol name={expanded ? "chevron.up" : "chevron.right"} size={20} color="#9ca3af" />
        </View>
        {expanded && (
          <View style={styles.faqAnswer}>
            <ThemedText style={[styles.faqAnswerText, { color: colors.textSub }]}>{answer}</ThemedText>
          </View>
        )}
      </View>
    </TouchableOpacity>
  );
}

export default function SupportScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [subject, setSubject] = useState('');

  return (
    <ThemedView style={styles.container}>
      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <View style={styles.header}>
          <TouchableOpacity 
            onPress={() => router.back()}
            style={styles.backButton}
          >
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <ThemedText style={styles.headerTitle}>Написать в поддержку</ThemedText>
          <View style={{ width: 40 }} />
        </View>

        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          <View style={styles.infoBanner}>
            <View style={[styles.infoCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
              <View style={[styles.supportIconBox, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]}>
                <IconSymbol name="person.2.fill" size={20} color="#13ec5b" />
              </View>
              <View style={styles.infoTextContainer}>
                <ThemedText style={styles.infoTitle}>Мы на связи</ThemedText>
                <ThemedText style={[styles.infoSubtitle, { color: colors.textSub }]}>Обычно мы отвечаем в течение 15 минут.</ThemedText>
              </View>
            </View>
          </View>

          <View style={styles.formSection}>
            <View style={styles.inputGroup}>
              <ThemedText style={styles.label}>Тема обращения</ThemedText>
              <TouchableOpacity style={[styles.pickerButton, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <ThemedText style={[styles.pickerText, !subject && { color: colors.textSub }]}>
                  {subject || 'Выберите тему'}
                </ThemedText>
                <IconSymbol name="chevron.down" size={20} color={colors.textSub} />
              </TouchableOpacity>
            </View>

            <View style={styles.inputGroup}>
              <View style={styles.labelRow}>
                <ThemedText style={styles.label}>Номер заказа</ThemedText>
                <ThemedText style={[styles.optionalLabel, { color: colors.textSub }]}>(необязательно)</ThemedText>
              </View>
              <TextInput
                style={[styles.input, { backgroundColor: colors.surface, borderColor: colors.border, color: colors.text }]}
                placeholder="Например, 12345"
                placeholderTextColor={colors.textSub}
              />
            </View>

            <View style={styles.inputGroup}>
              <ThemedText style={styles.label}>Сообщение</ThemedText>
              <TextInput
                style={[styles.textArea, { backgroundColor: colors.surface, borderColor: colors.border, color: colors.text }]}
                placeholder="Опишите вашу проблему подробно..."
                placeholderTextColor={colors.textSub}
                multiline
                numberOfLines={6}
                textAlignVertical="top"
              />
            </View>

            <TouchableOpacity style={styles.attachButton}>
              <IconSymbol name="paperclip" size={20} color="#13ec5b" />
              <ThemedText style={styles.attachText}>Прикрепить фото или файл</ThemedText>
            </TouchableOpacity>
          </View>

          <View style={styles.faqSection}>
            <ThemedText style={styles.faqTitle}>Частые вопросы</ThemedText>
            
            <FAQItem 
              icon="shippingbox.fill" 
              question="Как отследить курьера?" 
              answer="Вы можете отследить статус вашего заказа в реальном времени в разделе 'Заказы'. Как только курьер примет заказ, на карте отобразится его текущее местоположение."
            />

            <FAQItem 
              icon="creditcard.fill" 
              question="Условия возврата средств" 
              answer="Возврат средств осуществляется на ту же карту, с которой была произведена оплата. Обычно это занимает от 1 до 5 рабочих дней в зависимости от вашего банка."
            />
          </View>
        </ScrollView>

        <View style={[styles.stickyFooter, { backgroundColor: colors.background, borderTopColor: colors.border }]}>
          <TouchableOpacity 
            style={[styles.submitButton, { backgroundColor: colors.primary }]}
            onPress={() => router.push('/support-success')}
          >
            <ThemedText style={styles.submitButtonText}>Отправить сообщение</ThemedText>
            <IconSymbol name="paperplane.fill" size={20} color="#052e12" />
          </TouchableOpacity>
        </View>
      </KeyboardAvoidingView>
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
    paddingTop: 48,
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    flex: 1,
    textAlign: 'center',
    fontSize: 18,
    fontWeight: '700',
  },
  scrollContent: {
    paddingBottom: 120,
  },
  infoBanner: {
    padding: 16,
  },
  infoCard: {
    flexDirection: 'row',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    gap: 12,
  },
  supportIconBox: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  infoTextContainer: {
    flex: 1,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '700',
  },
  infoSubtitle: {
    fontSize: 14,
  },
  formSection: {
    paddingHorizontal: 16,
    gap: 16,
  },
  inputGroup: {
    gap: 8,
  },
  labelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    marginLeft: 4,
  },
  optionalLabel: {
    fontSize: 12,
  },
  pickerButton: {
    height: 56,
    borderRadius: 16,
    borderWidth: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
  },
  pickerText: {
    fontSize: 16,
  },
  input: {
    height: 56,
    borderRadius: 16,
    borderWidth: 1,
    paddingHorizontal: 16,
    fontSize: 16,
  },
  textArea: {
    minHeight: 140,
    borderRadius: 16,
    borderWidth: 1,
    padding: 16,
    fontSize: 16,
  },
  attachButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    paddingHorizontal: 4,
  },
  attachText: {
    color: '#13ec5b',
    fontSize: 14,
    fontWeight: '600',
  },
  faqSection: {
    padding: 16,
    marginTop: 8,
  },
  faqTitle: {
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 12,
    marginLeft: 4,
  },
  faqItem: {
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 12,
  },
  faqHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  faqItemLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flex: 1,
  },
  faqIconBox: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  faqItemText: {
    fontSize: 14,
    fontWeight: '600',
    flex: 1,
  },
  faqAnswer: {
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.05)',
  },
  faqAnswerText: {
    fontSize: 14,
    lineHeight: 20,
  },
  stickyFooter: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    padding: 16,
    borderTopWidth: 1,
  },
  submitButton: {
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
  submitButtonText: {
    color: '#052e12',
    fontSize: 16,
    fontWeight: '700',
  },
});
