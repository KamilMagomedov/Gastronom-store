
import React, { useState, useEffect } from 'react';
import { StyleSheet, View, TouchableOpacity, ScrollView, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import Svg, { Circle } from 'react-native-svg';

export default function CodeNotReceivedScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const [timer, setTimer] = useState(42);

  useEffect(() => {
    const interval = setInterval(() => {
      setTimer((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  const progress = (timer / 60) * 100;
  const radius = 16;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (progress / 100) * circumference;

  const isTimerFinished = timer === 0;

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity 
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.helpHeaderButton}>
          <ThemedText style={styles.helpHeaderText}>Помощь</ThemedText>
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <View style={styles.heroContainer}>
          <View style={[styles.iconCircle, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.1)' : 'rgba(19, 236, 91, 0.1)' }]}>
            <IconSymbol name="envelope.badge.fill" size={60} color="#13ec5b" />
            <View style={[styles.alertBadge, { backgroundColor: colors.surface, borderColor: colors.background }]}>
              <IconSymbol name="exclamationmark.triangle.fill" size={16} color="#f97316" />
            </View>
          </View>
        </View>

        <View style={styles.textContainer}>
          <ThemedText style={styles.title}>Код не пришел?</ThemedText>
          <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
            Мы отправили письмо с кодом на вашу почту. Если его нет во входящих, проверьте папку «Спам» или убедитесь в правильности адреса.
          </ThemedText>
        </View>

        <View style={[styles.emailCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          <View>
            <ThemedText style={[styles.emailLabel, { color: colors.textSub }]}>Электронная почта</ThemedText>
            <ThemedText style={styles.emailText}>ivanov.alex@yandex.ru</ThemedText>
          </View>
          <TouchableOpacity style={styles.editButton}>
            <IconSymbol name="pencil" size={20} color="#13ec5b" />
          </TouchableOpacity>
        </View>

        <View style={styles.timerSection}>
          <View style={styles.progressContainer}>
            <Svg width="100" height="100" viewBox="0 0 40 40">
              <Circle
                cx="20"
                cy="20"
                r={radius}
                stroke={colorScheme === 'dark' ? '#1a2e22' : '#f1f5f9'}
                strokeWidth="2.5"
                fill="none"
              />
              <Circle
                cx="20"
                cy="20"
                r={radius}
                stroke="#13ec5b"
                strokeWidth="2.5"
                fill="none"
                strokeDasharray={`${circumference} ${circumference}`}
                strokeDashoffset={strokeDashoffset}
                strokeLinecap="round"
                transform="rotate(-90 20 20)"
              />
            </Svg>
            <View style={styles.timerTextContainer}>
              <ThemedText style={styles.timerValue}>00:{timer < 10 ? `0${timer}` : timer}</ThemedText>
            </View>
          </View>
          <ThemedText style={[styles.timerLabel, { color: colors.textSub }]}>
            {isTimerFinished ? 'Вы можете отправить код снова' : 'Повторная отправка через'}
          </ThemedText>
        </View>

        <View style={styles.footer}>
          <TouchableOpacity 
            style={[
              styles.actionButton, 
              { 
                backgroundColor: isTimerFinished ? colors.primary : colors.surface, 
                opacity: isTimerFinished ? 1 : 0.5 
              }
            ]} 
            disabled={!isTimerFinished}
            onPress={() => {
              if (isTimerFinished) {
                setTimer(60);
              }
            }}
          >
            <IconSymbol 
              name="envelope.fill" 
              size={20} 
              color={isTimerFinished ? '#0a2e16' : colors.textSub} 
            />
            <ThemedText 
              style={[
                styles.actionButtonText, 
                { color: isTimerFinished ? '#0a2e16' : colors.textSub }
              ]}
            >
              Отправить письмо снова
            </ThemedText>
          </TouchableOpacity>

          <TouchableOpacity style={styles.supportButton} onPress={() => router.push('/support')}>
            <ThemedText style={[styles.supportText, { color: colors.textSub }]}>Всё еще не пришло? </ThemedText>
            <ThemedText style={styles.supportLink}>Написать в поддержку</ThemedText>
          </TouchableOpacity>
        </View>
      </ScrollView>
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
  helpHeaderButton: {
    paddingHorizontal: 12,
    paddingVertical: 4,
  },
  helpHeaderText: {
    color: '#13ec5b',
    fontSize: 14,
    fontWeight: '700',
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingBottom: 32,
  },
  heroContainer: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  iconCircle: {
    width: 128,
    height: 128,
    borderRadius: 64,
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
  },
  alertBadge: {
    position: 'absolute',
    top: 0,
    right: 0,
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 4,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
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
  },
  emailCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    marginBottom: 32,
  },
  emailLabel: {
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 1,
    marginBottom: 4,
  },
  emailText: {
    fontSize: 18,
    fontWeight: '700',
  },
  editButton: {
    padding: 8,
  },
  timerSection: {
    alignItems: 'center',
    marginBottom: 32,
  },
  progressContainer: {
    width: 100,
    height: 100,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  timerTextContainer: {
    position: 'absolute',
    alignItems: 'center',
    justifyContent: 'center',
  },
  timerValue: {
    fontSize: 20,
    fontWeight: '800',
  },
  timerLabel: {
    fontSize: 14,
    fontWeight: '600',
  },
  footer: {
    gap: 12,
    marginTop: 'auto',
  },
  actionButton: {
    height: 56,
    borderRadius: 16,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 12,
  },
  actionButtonText: {
    fontSize: 16,
    fontWeight: '700',
  },
  supportButton: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginTop: 16,
  },
  supportText: {
    fontSize: 14,
    fontWeight: '500',
  },
  supportLink: {
    color: '#13ec5b',
    fontSize: 14,
    fontWeight: '700',
  },
});
