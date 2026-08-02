
import React, { useState } from 'react';
import { StyleSheet, View, TextInput, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';
import { ApiService, ApiError } from '@/services/api';

export default function ResetPasswordScreen() {
  const router = useRouter();
  const { token, email } = useLocalSearchParams<{ token: string; email: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { login } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});

  const getError = (field: string) => fieldErrors[field]?.[0] ?? null;

  const clearError = (field: string) => {
    if (fieldErrors[field]) setFieldErrors(prev => ({ ...prev, [field]: [] }));
  };

  const handleResetPassword = async () => {
    setFieldErrors({});
    setIsLoading(true);
    try {
      const response = await ApiService.resetPassword(
        token ?? '',
        email ?? '',
        password,
        passwordConfirmation,
      );
      if (response.data.token) {
        await login(response.data.token);
        router.replace('/(tabs)');
      } else {
        router.replace('/login');
      }
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
            style={[styles.backButton, { backgroundColor: colorScheme === 'dark' ? 'rgba(255,255,255,0.05)' : '#fff' }]}
          >
            <IconSymbol name="arrow.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <ThemedText style={styles.headerTitle}>Сброс пароля</ThemedText>
          <View style={{ width: 40 }} />
        </View>

        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.heroContainer}>
            <View style={[styles.iconBox, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.05)' : 'rgba(19, 236, 91, 0.1)' }]}>
              <IconSymbol name="lock.fill" size={40} color="#13ec5b" />
              <View style={styles.badge} />
            </View>
          </View>

          <View style={styles.textContainer}>
            <ThemedText style={styles.title}>Новый пароль</ThemedText>
            <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
              Ваш новый пароль должен отличаться от ранее использованных паролей.
            </ThemedText>
          </View>

          <View style={styles.form}>
            <View style={styles.inputGroup}>
              <ThemedText style={styles.label}>Новый пароль</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: getError('password') ? '#ef4444' : colors.border }]}>
                <IconSymbol name="lock.fill" size={22} color={colors.textSub} style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, { color: colors.text }]}
                  placeholder="Введите пароль"
                  placeholderTextColor={colors.textSub}
                  secureTextEntry={!showPassword}
                  value={password}
                  onChangeText={(v) => { setPassword(v); clearError('password'); }}
                />
                <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                  <IconSymbol
                    name={showPassword ? 'eye.fill' : 'eye.slash.fill'}
                    size={22}
                    color={colors.textSub}
                  />
                </TouchableOpacity>
              </View>
              {getError('password') && <ThemedText style={styles.errorText}>{getError('password')}</ThemedText>}
            </View>

            <View style={styles.inputGroup}>
              <ThemedText style={styles.label}>Подтверждение</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: getError('password_confirmation') ? '#ef4444' : colors.border }]}>
                <IconSymbol name="lock.fill" size={22} color={colors.textSub} style={styles.inputIcon} />
                <TextInput
                  style={[styles.input, { color: colors.text }]}
                  placeholder="Повторите пароль"
                  placeholderTextColor={colors.textSub}
                  secureTextEntry={!showConfirmPassword}
                  value={passwordConfirmation}
                  onChangeText={(v) => { setPasswordConfirmation(v); clearError('password_confirmation'); }}
                />
                <TouchableOpacity onPress={() => setShowConfirmPassword(!showConfirmPassword)}>
                  <IconSymbol
                    name={showConfirmPassword ? 'eye.fill' : 'eye.slash.fill'}
                    size={22}
                    color={colors.textSub}
                  />
                </TouchableOpacity>
              </View>
              {getError('password_confirmation') && <ThemedText style={styles.errorText}>{getError('password_confirmation')}</ThemedText>}
            </View>
          </View>

          <View style={styles.requirements}>
            <View style={[styles.requirementBadge, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.1)' : '#f0fdf4' }]}>
              <IconSymbol name="checkmark.circle.fill" size={16} color="#13ec5b" />
              <ThemedText style={styles.requirementText}>Минимум 8 символов</ThemedText>
            </View>
          </View>

          <View style={styles.spacer} />

          <View style={styles.actionContainer}>
            <TouchableOpacity
              style={[styles.submitButton, { backgroundColor: isLoading ? colors.border : colors.primary, opacity: isLoading ? 0.6 : 1 }]}
              onPress={handleResetPassword}
              disabled={isLoading}
            >
              <ThemedText style={styles.submitButtonText}>
                {isLoading ? 'Сохранение...' : 'Сохранить пароль'}
              </ThemedText>
              {!isLoading && <IconSymbol name="checkmark" size={20} color="#0a2e16" />}
            </TouchableOpacity>

            <TouchableOpacity style={styles.cancelButton} onPress={() => router.replace('/login')}>
              <ThemedText style={[styles.cancelText, { color: colors.textSub }]}>Отменить</ThemedText>
            </TouchableOpacity>
          </View>
        </ScrollView>
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
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
    elevation: 2,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.05)',
  },
  headerTitle: {
    flex: 1,
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '600',
    color: '#9ca3af',
    textTransform: 'uppercase',
    letterSpacing: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingTop: 24,
    paddingBottom: 32,
  },
  heroContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  iconBox: {
    width: 80,
    height: 80,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    transform: [{ rotate: '3deg' }],
  },
  badge: {
    position: 'absolute',
    top: -2,
    right: -2,
    width: 16,
    height: 16,
    borderRadius: 8,
    backgroundColor: '#13ec5b',
    borderWidth: 2,
    borderColor: '#f6f8f6',
  },
  textContainer: {
    alignItems: 'center',
    marginBottom: 40,
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    marginBottom: 12,
  },
  subtitle: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
  },
  form: {
    gap: 20,
    marginBottom: 16,
  },
  inputGroup: {
    gap: 8,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    marginLeft: 4,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    height: 64,
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
    fontWeight: '500',
  },
  requirements: {
    flexDirection: 'row',
    justifyContent: 'center',
  },
  requirementBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  requirementText: {
    fontSize: 12,
    fontWeight: '600',
  },
  spacer: {
    flex: 1,
  },
  actionContainer: {
    marginTop: 32,
  },
  submitButton: {
    height: 64,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  submitButtonText: {
    color: '#0a2e16',
    fontSize: 18,
    fontWeight: '700',
  },
  cancelButton: {
    marginTop: 16,
    alignItems: 'center',
  },
  cancelText: {
    fontSize: 14,
    fontWeight: '600',
  },
  errorText: {
    fontSize: 12,
    color: '#ef4444',
    marginTop: 4,
    marginLeft: 4,
  },
});
