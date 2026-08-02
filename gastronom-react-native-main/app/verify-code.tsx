
import React, { useState, useRef, useEffect } from 'react';
import { StyleSheet, View, TextInput, TouchableOpacity, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function VerifyCodeScreen() {
  const router = useRouter();
  const { email } = useLocalSearchParams<{ email: string }>();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [code, setCode] = useState(['', '', '', '']);
  const inputs = useRef<TextInput[]>([]);
  const [timer, setTimer] = useState(59);

  useEffect(() => {
    const interval = setInterval(() => {
      setTimer((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const handleCodeChange = (text: string, index: number) => {
    const newCode = [...code];
    newCode[index] = text;
    setCode(newCode);

    if (text.length === 1 && index < 3) {
      inputs.current[index + 1].focus();
    }
  };

  const handleKeyPress = (e: any, index: number) => {
    if (e.nativeEvent.key === 'Backspace' && code[index] === '' && index > 0) {
      inputs.current[index - 1].focus();
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
          <ThemedText style={styles.headerTitle}>Восстановление</ThemedText>
          <View style={{ width: 40 }} />
        </View>

        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.heroContainer}>
            <View style={[styles.iconBox, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.05)' : 'rgba(19, 236, 91, 0.1)' }]}>
              <IconSymbol name="lock.open.fill" size={40} color="#13ec5b" />
              <View style={styles.badge} />
            </View>
          </View>

          <View style={styles.textContainer}>
            <ThemedText style={styles.title}>Код подтверждения</ThemedText>
            <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
              Мы отправили 4-значный код на ваш номер {'\n'}
              <ThemedText style={[styles.phoneText, { color: colors.text }]}>+7 (900) 000-00-00</ThemedText>
            </ThemedText>
          </View>

          <View style={styles.codeContainer}>
            {code.map((digit, index) => (
              <TextInput
                key={index}
                ref={(ref) => (inputs.current[index] = ref as TextInput)}
                style={[
                  styles.codeInput,
                  { 
                    color: colors.text, 
                    backgroundColor: colors.surface,
                    borderColor: digit ? '#13ec5b' : colors.border
                  },
                  digit ? styles.codeInputActive : null
                ]}
                maxLength={1}
                keyboardType="number-pad"
                value={digit}
                onChangeText={(text) => handleCodeChange(text, index)}
                onKeyPress={(e) => handleKeyPress(e, index)}
                autoFocus={index === 0}
              />
            ))}
          </View>

          <View style={styles.timerContainer}>
            <View style={[styles.timerBadge, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
              <ThemedText style={styles.timerText}>
                Отправить код повторно через 00:{timer < 10 ? `0${timer}` : timer}
              </ThemedText>
            </View>
          </View>

          <View style={styles.spacer} />

          <View style={styles.actionContainer}>
            <TouchableOpacity
              style={[styles.submitButton, { backgroundColor: colors.primary }]}
              onPress={() => {
                const token = code.join('');
                router.push({ pathname: '/reset-password', params: { token, email: email ?? '' } });
              }}
            >
              <ThemedText style={styles.submitButtonText}>Подтвердить</ThemedText>
              <IconSymbol name="arrow.right" size={20} color="#0a2e16" />
            </TouchableOpacity>

            <TouchableOpacity style={styles.helpButton} onPress={() => router.push('/code-not-received')}>
              <ThemedText style={[styles.helpText, { color: colors.textSub }]}>Не пришел код?</ThemedText>
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
  phoneText: {
    fontWeight: '700',
  },
  codeContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
    marginBottom: 32,
  },
  codeInput: {
    width: 60,
    height: 64,
    borderRadius: 16,
    borderWidth: 1,
    textAlign: 'center',
    fontSize: 24,
    fontWeight: '700',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 2,
    elevation: 1,
  },
  codeInputActive: {
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
  },
  timerContainer: {
    alignItems: 'center',
  },
  timerBadge: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 999,
  },
  timerText: {
    color: '#13ec5b',
    fontSize: 14,
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
  helpButton: {
    marginTop: 16,
    alignItems: 'center',
  },
  helpText: {
    fontSize: 14,
    fontWeight: '600',
  },
});
