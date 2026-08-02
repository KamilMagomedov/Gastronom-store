
import React, { useState } from 'react';
import { StyleSheet, View, TextInput, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform, ImageBackground } from 'react-native';
import { useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';
import { ApiService, ApiError } from '@/services/api';

export default function LoginScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { login } = useAuth();
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);

  const getError = (field: string) => fieldErrors[field]?.[0] ?? null;

  const clearError = (field: string) => {
    if (fieldErrors[field]) setFieldErrors(prev => ({ ...prev, [field]: [] }));
  };

  const handleLogin = async () => {
    setFieldErrors({});
    setGeneralError(null);
    setIsLoading(true);
    try {
      const response = await ApiService.login({ email, password });
      await login(response.data.token);
      router.replace('/(tabs)');
    } catch (error) {
      const apiError = error as ApiError;
      setFieldErrors(ApiService.getFieldErrors(apiError));
      if (apiError.message) setGeneralError(apiError.message);
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
            style={styles.iconButton}
          >
            <IconSymbol name="chevron.left" size={24} color={colors.text} />
          </TouchableOpacity>
          <ThemedText style={styles.headerTitle}>Вход</ThemedText>
          <View style={{ width: 48 }} />
        </View>

        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.imageContainer}>
            <ImageBackground
              source={{ uri: "https://lh3.googleusercontent.com/aida-public/AB6AXuBqaXJ2sNCgosv5jRqgNZ3Fn1_Nf-WkdyCxSraEq00ZGmQqrIl4GXnFPlgyqdceF2P97R_7wIlW_ZtC3S0K77gSmKsc4btdvtLAbbiKL4UYxDUwmzYEZEkbBriJz3C94fGLfjQQYIm9ZdXYPwoJGCyiEWZBBkmYSQo0sawW6MfmKkFxLdIkUlj1UthYHfRYF-ahNYApEa_wL0SHUf7J4RMDWAMvjXuVRwLdFpQCoCTt8ReC39Scl-3cuUu-wGWWDsKoFDcMKCJxBbA" }}
              style={styles.image}
              imageStyle={{ borderRadius: 24 }}
            />
          </View>

          <View style={styles.content}>
            <View style={styles.titleContainer}>
              <ThemedText style={styles.title}>С возвращением!</ThemedText>
              <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
                Войдите, чтобы продолжить покупки
              </ThemedText>
            </View>

            <View style={styles.form}>
              {generalError && (
                <View style={styles.errorBanner}>
                  <IconSymbol name="exclamationmark.circle.fill" size={16} color="#ef4444" />
                  <ThemedText style={styles.errorBannerText}>{generalError}</ThemedText>
                </View>
              )}

              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Email</ThemedText>
                <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: getError('email') ? '#ef4444' : 'transparent' }]}>
                  <TextInput
                    style={[styles.input, { color: colors.text }]}
                    placeholder="Введите ваш email"
                    placeholderTextColor={colors.textSub}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    value={email}
                    onChangeText={(v) => { setEmail(v); clearError('email'); }}
                  />
                  <IconSymbol name="person.fill" size={20} color={colors.textSub} style={styles.inputIcon} />
                </View>
                {getError('email') && <ThemedText style={styles.errorText}>{getError('email')}</ThemedText>}
              </View>

              <View style={styles.inputGroup}>
                <ThemedText style={styles.label}>Пароль</ThemedText>
                <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: getError('password') ? '#ef4444' : 'transparent' }]}>
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
                      name={showPassword ? "eye.fill" : "eye.slash.fill"} 
                      size={20} 
                      color={colors.textSub} 
                      style={styles.inputIcon}
                    />
                  </TouchableOpacity>
                </View>
                {getError('password') && <ThemedText style={styles.errorText}>{getError('password')}</ThemedText>}
              </View>

              <TouchableOpacity style={styles.forgotPassword} onPress={() => router.push('/forgot-password')}>
                <ThemedText style={styles.linkText}>Забыли пароль?</ThemedText>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.submitButton, { backgroundColor: isLoading ? colors.border : colors.primary, opacity: isLoading ? 0.6 : 1 }]}
                onPress={handleLogin}
                disabled={isLoading}
              >
                <ThemedText style={styles.submitButtonText}>{isLoading ? 'Вход...' : 'Войти'}</ThemedText>
                <IconSymbol name="door.right.hand.open" size={20} color="#0d3b1d" />
              </TouchableOpacity>

              <View style={styles.dividerContainer}>
                <View style={[styles.divider, { backgroundColor: colorScheme === 'dark' ? '#374151' : '#e5e7eb' }]} />
                <ThemedText style={[styles.dividerText, { color: colors.textSub, backgroundColor: colors.background }]}>
                  или войти через
                </ThemedText>
              </View>

              <View style={styles.socialButtons}>
                <TouchableOpacity style={[styles.socialCircle, { backgroundColor: colors.surface, borderColor: colorScheme === 'dark' ? '#374151' : '#e5e7eb' }]}>
                  <View style={styles.yandexIcon}>
                    <ThemedText style={styles.yandexText}>Я</ThemedText>
                  </View>
                </TouchableOpacity>

                <TouchableOpacity style={[styles.socialCircle, { backgroundColor: colors.surface, borderColor: colorScheme === 'dark' ? '#374151' : '#e5e7eb' }]}>
                  <IconSymbol name="phone.fill" size={24} color={colors.text} />
                </TouchableOpacity>
              </View>

              <View style={styles.footer}>
                <ThemedText style={styles.footerText}>
                  Нет аккаунта?{' '}
                  <ThemedText style={styles.linkText} onPress={() => router.push('/signup')}>
                    Зарегистрироваться
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
    width: 48,
    height: 48,
    borderRadius: 24,
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
    paddingHorizontal: 16,
  },
  imageContainer: {
    width: '100%',
    height: 220,
    marginTop: 16,
    borderRadius: 24,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  image: {
    width: '100%',
    height: '100%',
  },
  content: {
    paddingVertical: 24,
  },
  titleContainer: {
    alignItems: 'center',
    marginBottom: 24,
  },
  title: {
    fontSize: 32,
    fontWeight: '700',
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    fontWeight: '500',
    marginTop: 8,
    textAlign: 'center',
  },
  form: {
    gap: 16,
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
    height: 56,
    borderRadius: 16,
    paddingHorizontal: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  input: {
    flex: 1,
    fontSize: 16,
    height: '100%',
    fontWeight: '500',
  },
  inputIcon: {
    marginLeft: 12,
  },
  forgotPassword: {
    alignSelf: 'flex-end',
    marginBottom: 8,
  },
  linkText: {
    color: '#13ec5b',
    fontWeight: '700',
    fontSize: 14,
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
    color: '#0d3b1d',
    fontSize: 18,
    fontWeight: '700',
  },
  dividerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 24,
  },
  divider: {
    flex: 1,
    height: 1,
  },
  dividerText: {
    paddingHorizontal: 16,
    fontSize: 14,
    fontWeight: '500',
  },
  socialButtons: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
    marginBottom: 24,
  },
  socialCircle: {
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  yandexIcon: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#FC3F1D',
    alignItems: 'center',
    justifyContent: 'center',
  },
  yandexText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
  },
  footer: {
    alignItems: 'center',
    paddingBottom: 20,
  },
  footerText: {
    fontSize: 15,
    fontWeight: '500',
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
