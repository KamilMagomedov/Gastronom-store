
import React, { useState } from 'react';
import { StyleSheet, View, TextInput, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { LinearGradient } from 'expo-linear-gradient';
import { ApiService, ApiError } from '@/services/api';

export default function ForgotPasswordScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [email, setEmail] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  const getError = (field: string) => fieldErrors[field]?.[0] ?? null;

  const handleForgotPassword = async () => {
    setFieldErrors({});
    setIsLoading(true);
    try {
      await ApiService.forgotPassword(email);
      router.push({ pathname: '/verify-code', params: { email } });
    } catch (error) {
      const apiError = error as ApiError;
      setFieldErrors(ApiService.getFieldErrors(apiError));
    } finally {
      setIsLoading(false);
    }
  };

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
          <ThemedText style={styles.headerTitle}>Восстановление</ThemedText>
          <View style={{ width: 40 }} />
        </View>

        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.heroContainer}>
            <View style={[styles.iconCircle, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.2)' : 'rgba(19, 236, 91, 0.1)' }]}>
              <IconSymbol name="lock.fill" size={48} color="#13ec5b" />
              <View style={styles.dot1} />
              <View style={styles.dot2} />
            </View>
          </View>

          <View style={styles.textContainer}>
            <ThemedText style={styles.title}>Забыли пароль?</ThemedText>
            <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
              Не волнуйтесь! Введите ваш Email или телефон, и мы отправим вам инструкцию по сбросу пароля.
            </ThemedText>
          </View>

          <View style={styles.form}>
            <View style={styles.inputGroup}>
              <ThemedText style={styles.label}>Email или телефон</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: getError('email') ? '#ef4444' : colors.border }]}>
                <IconSymbol name="envelope.fill" size={20} color={colors.textSub} style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, { color: colors.text }]}
                  placeholder="example@mail.ru"
                  placeholderTextColor={colors.textSub}
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={email}
                  onChangeText={(v) => { setEmail(v); if (fieldErrors.email) setFieldErrors(prev => ({ ...prev, email: [] })); }}
                />
              </View>
              {getError('email') && <ThemedText style={styles.errorText}>{getError('email')}</ThemedText>}
            </View>

            <TouchableOpacity
              style={[styles.submitButton, { backgroundColor: isLoading ? colors.border : colors.primary, opacity: isLoading ? 0.6 : 1 }]}
              onPress={handleForgotPassword}
              disabled={isLoading}
            >
              <ThemedText style={styles.submitButtonText}>
                {isLoading ? 'Отправка...' : 'Сбросить пароль'}
              </ThemedText>
            </TouchableOpacity>
          </View>

          <View style={styles.footer}>
            <ThemedText style={styles.footerText}>
              Вспомнили пароль?{' '}
              <ThemedText style={styles.linkText} onPress={() => router.push('/login')}>
                Войти
              </ThemedText>
            </ThemedText>

            <TouchableOpacity style={styles.helpButton}>
              <IconSymbol name="questionmark.circle" size={18} color={colors.textSub} />
              <ThemedText style={[styles.helpText, { color: colors.textSub }]}>Нужна помощь?</ThemedText>
            </TouchableOpacity>
          </View>
        </ScrollView>
        <LinearGradient
          colors={['transparent', 'rgba(19, 236, 91, 0.1)', 'transparent']}
          start={{ x: 0, y: 0.5 }}
          end={{ x: 1, y: 0.5 }}
          style={styles.bottomGradient}
        />
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
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingTop: 32,
    paddingBottom: 32,
  },
  heroContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  iconCircle: {
    width: 96,
    height: 96,
    borderRadius: 48,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  dot1: {
    position: 'absolute',
    right: -8,
    top: 0,
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#13ec5b',
    opacity: 0.6,
  },
  dot2: {
    position: 'absolute',
    left: -4,
    bottom: 8,
    width: 12,
    height: 12,
    borderRadius: 6,
    backgroundColor: '#13ec5b',
    opacity: 0.4,
  },
  textContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    textAlign: 'center',
    marginBottom: 12,
  },
  subtitle: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
    paddingHorizontal: 8,
  },
  form: {
    gap: 24,
  },
  inputGroup: {
    gap: 8,
  },
  label: {
    fontSize: 14,
    fontWeight: '700',
    marginLeft: 4,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 56,
    borderRadius: 16,
    borderWidth: 1,
    paddingHorizontal: 16,
  },
  inputIcon: {
    marginRight: 12,
  },
  input: {
    flex: 1,
    fontSize: 16,
    height: '100%',
  },
  submitButton: {
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 14,
    elevation: 8,
  },
  submitButtonText: {
    color: '#111813',
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.5,
  },
  footer: {
    marginTop: 'auto',
    alignItems: 'center',
    gap: 16,
    paddingTop: 32,
  },
  footerText: {
    fontSize: 14,
    fontWeight: '500',
  },
  linkText: {
    color: '#13ec5b',
    fontWeight: '700',
  },
  helpButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  helpText: {
    fontSize: 14,
    fontWeight: '500',
  },
  bottomGradient: {
    height: 2,
    width: '100%',
  },
  errorText: {
    fontSize: 12,
    color: '#ef4444',
    marginTop: 4,
    marginLeft: 4,
  },
});
