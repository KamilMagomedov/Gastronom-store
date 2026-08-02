
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useAuth } from '@/context/auth-context';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { ApiError, ApiService, RegistrationData } from '@/services/api';
import { useRouter } from 'expo-router';
import React, { useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';

export default function SignupScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { login } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const [formData, setFormData] = useState<RegistrationData>({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  const [fieldErrors, setFieldErrors] = useState<{ [key: string]: string[] }>({});
  const [generalError, setGeneralError] = useState<string | null>(null);

  const handleInputChange = (field: keyof RegistrationData, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (fieldErrors[field]) {
      setFieldErrors((prev) => ({ ...prev, [field]: [] }));
    }
  };

  const handleSignup = async () => {
    setIsLoading(true);
    setFieldErrors({});
    setGeneralError(null);

    try {
      const response = await ApiService.register(formData);
      if (response.data.token) {
        await login(response.data.token);
        router.replace('/(tabs)');
      } else {
        setGeneralError('Не удалось получить токен авторизации');
      }
    } catch (error) {
      const apiError = error as ApiError;
      setFieldErrors(ApiService.getFieldErrors(apiError));
      if (apiError.message) setGeneralError(apiError.message);
    } finally {
      setIsLoading(false);
    }
  };

  const getFieldError = (field: string) => {
    const errors = fieldErrors[field];
    return errors && errors.length > 0 ? errors[0] : null;
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
            style={[
              styles.iconButton,
              {
                backgroundColor:
                  colorScheme === 'dark' ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)',
              },
            ]}
          >
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <View style={{ flex: 1 }} />
        </View>

        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.content}>
            <ThemedText style={styles.title}>Создать аккаунт</ThemedText>
            <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
              Заполните данные, чтобы начать заказывать свежие продукты с доставкой.
            </ThemedText>

            <View style={styles.form}>
              {generalError && (
                <View style={styles.errorBanner}>
                  <IconSymbol name="exclamationmark.circle.fill" size={16} color="#ef4444" />
                  <ThemedText style={styles.errorBannerText}>{generalError}</ThemedText>
                </View>
              )}

              {/* Name */}
              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Имя</ThemedText>
                <View
                  style={[
                    styles.inputWrapper,
                    {
                      backgroundColor: colors.background,
                      borderColor: getFieldError('name') ? '#ef4444' : colors.border,
                    },
                  ]}
                >
                  <IconSymbol
                    name="person.fill"
                    size={20}
                    color={colors.textSub}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={[styles.input, { color: colors.text }]}
                    placeholder="Ваше имя"
                    placeholderTextColor={colors.textSub}
                    value={formData.name}
                    onChangeText={(value) => handleInputChange('name', value)}
                  />
                </View>
                {getFieldError('name') && (
                  <ThemedText style={styles.errorText}>{getFieldError('name')}</ThemedText>
                )}
              </View>

              {/* Email */}
              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Email</ThemedText>
                <View
                  style={[
                    styles.inputWrapper,
                    {
                      backgroundColor: colors.background,
                      borderColor: getFieldError('email') ? '#ef4444' : colors.border,
                    },
                  ]}
                >
                  <IconSymbol
                    name="envelope.fill"
                    size={20}
                    color={colors.textSub}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={[styles.input, { color: colors.text }]}
                    placeholder="example@mail.com"
                    placeholderTextColor={colors.textSub}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    value={formData.email}
                    onChangeText={(value) => handleInputChange('email', value)}
                  />
                </View>
                {getFieldError('email') && (
                  <ThemedText style={styles.errorText}>{getFieldError('email')}</ThemedText>
                )}
              </View>

              {/* Password */}
              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Пароль</ThemedText>
                <View
                  style={[
                    styles.inputWrapper,
                    {
                      backgroundColor: colors.background,
                      borderColor: getFieldError('password') ? '#ef4444' : colors.border,
                    },
                  ]}
                >
                  <IconSymbol
                    name="lock.fill"
                    size={20}
                    color={colors.textSub}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={[styles.input, { color: colors.text }]}
                    placeholder="••••••••"
                    placeholderTextColor={colors.textSub}
                    secureTextEntry={!showPassword}
                    value={formData.password}
                    onChangeText={(value) => handleInputChange('password', value)}
                  />
                  <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                    <IconSymbol
                      name={showPassword ? 'eye.fill' : 'eye.slash.fill'}
                      size={20}
                      color={colors.textSub}
                    />
                  </TouchableOpacity>
                </View>
                {getFieldError('password') && (
                  <ThemedText style={styles.errorText}>{getFieldError('password')}</ThemedText>
                )}
              </View>

              {/* Confirm Password */}
              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Подтвердите пароль</ThemedText>
                <View
                  style={[
                    styles.inputWrapper,
                    {
                      backgroundColor: colors.background,
                      borderColor:
                        getFieldError('password_confirmation') ? '#ef4444' : colors.border,
                    },
                  ]}
                >
                  <IconSymbol
                    name="checkmark.circle.fill"
                    size={20}
                    color={colors.textSub}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={[styles.input, { color: colors.text }]}
                    placeholder="••••••••"
                    placeholderTextColor={colors.textSub}
                    secureTextEntry={!showConfirmPassword}
                    value={formData.password_confirmation}
                    onChangeText={(value) => handleInputChange('password_confirmation', value)}
                  />
                  <TouchableOpacity onPress={() => setShowConfirmPassword(!showConfirmPassword)}>
                    <IconSymbol
                      name={showConfirmPassword ? 'eye.fill' : 'eye.slash.fill'}
                      size={20}
                      color={colors.textSub}
                    />
                  </TouchableOpacity>
                </View>
                {getFieldError('password_confirmation') && (
                  <ThemedText style={styles.errorText}>
                    {getFieldError('password_confirmation')}
                  </ThemedText>
                )}
              </View>

              {/* Submit */}
              <TouchableOpacity
                style={[
                  styles.submitButton,
                  { backgroundColor: isLoading ? colors.border : colors.primary, opacity: isLoading ? 0.6 : 1 },
                ]}
                onPress={handleSignup}
                disabled={isLoading}
              >
                <ThemedText style={styles.submitButtonText}>
                  {isLoading ? 'Регистрация...' : 'Зарегистрироваться'}
                </ThemedText>
                {!isLoading && <IconSymbol name="arrow.right" size={20} color="#0d3b1d" />}
              </TouchableOpacity>

              <View style={styles.footer}>
                <ThemedText style={styles.footerText}>
                  Уже есть аккаунт?{' '}
                  <ThemedText style={styles.linkText} onPress={() => router.push('/login')}>
                    Войти
                  </ThemedText>
                </ThemedText>
              </View>
            </View>
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
  iconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  scrollContent: {
    flexGrow: 1,
  },
  content: {
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 32,
  },
  title: {
    fontSize: 32,
    fontWeight: '700',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 16,
    lineHeight: 24,
    marginBottom: 32,
  },
  form: {
    gap: 20,
  },
  inputGroup: {
    gap: 6,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
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
    borderRadius: 28,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginTop: 16,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  submitButtonText: {
    color: '#0d3b1d',
    fontSize: 17,
    fontWeight: '700',
  },
  footer: {
    marginTop: 16,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 16,
  },
  linkText: {
    color: '#13ec5b',
    fontWeight: '700',
  },
  errorText: {
    fontSize: 12,
    color: '#ef4444',
    marginTop: 4,
    marginLeft: 4,
  },
  errorBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: '#FEF2F2',
    borderColor: '#FECACA',
    borderWidth: 1,
    borderRadius: 12,
    padding: 12,
  },
  errorBannerText: {
    flex: 1,
    fontSize: 14,
    color: '#DC2626',
    fontWeight: '500',
  },
});
