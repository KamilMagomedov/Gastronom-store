
import React from 'react';
import { StyleSheet, View, TouchableOpacity, Animated } from 'react-native';
import { useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function SupportSuccessScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  return (
    <ThemedView style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity 
          onPress={() => router.replace('/(tabs)')}
          style={styles.closeButton}
        >
          <IconSymbol name="xmark" size={24} color={colors.textSub} />
        </TouchableOpacity>
      </View>

      <View style={styles.content}>
        <View style={styles.iconContainer}>
          <View style={[styles.pingCircle, { backgroundColor: 'rgba(19, 236, 91, 0.2)' }]} />
          <View style={[styles.iconCard, { backgroundColor: colors.surface, borderColor: 'rgba(19, 236, 91, 0.2)' }]}>
            <IconSymbol name="checkmark.circle.fill" size={80} color="#13ec5b" />
          </View>
        </View>

        <ThemedText style={styles.title}>Ваше сообщение отправлено</ThemedText>
        <ThemedText style={[styles.subtitle, { color: colors.textSub }]}>
          Спасибо, ваше сообщение будет рассмотрено службой технической поддержки в ближайшее время
        </ThemedText>
      </View>

      <View style={[styles.footer, { backgroundColor: colors.background, borderTopColor: colors.border }]}>
        <TouchableOpacity 
          style={[styles.mainButton, { backgroundColor: colors.primary }]}
          onPress={() => router.replace('/(tabs)')}
        >
          <ThemedText style={styles.mainButtonText}>Вернуться на главную</ThemedText>
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
    justifyContent: 'flex-end',
    paddingTop: 48,
    paddingHorizontal: 16,
    paddingBottom: 8,
  },
  closeButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  content: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    marginTop: -60,
  },
  iconContainer: {
    position: 'relative',
    marginBottom: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pingCircle: {
    position: 'absolute',
    width: 140,
    height: 140,
    borderRadius: 70,
    opacity: 0.2,
  },
  iconCard: {
    padding: 24,
    borderRadius: 70,
    borderWidth: 1,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 20,
    elevation: 4,
  },
  title: {
    fontSize: 24,
    fontWeight: '800',
    textAlign: 'center',
    marginBottom: 16,
    letterSpacing: -0.5,
  },
  subtitle: {
    fontSize: 16,
    textAlign: 'center',
    lineHeight: 24,
    maxWidth: 300,
  },
  footer: {
    padding: 16,
    borderTopWidth: 1,
    paddingBottom: 32,
  },
  mainButton: {
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  mainButtonText: {
    color: '#052e12',
    fontSize: 16,
    fontWeight: '700',
  },
});
