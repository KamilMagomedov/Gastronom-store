import React, { useState } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Switch, Platform } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useTheme } from '@/context/theme-context';

export default function SettingsScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const { theme, setThemeMode } = useTheme();
  const colors = Colors[theme];

  const [notifications, setNotifications] = useState(true);
  const [newsletter, setNewsletter] = useState(true);

  const toggleDarkMode = (value: boolean) => {
    setThemeMode(value ? 'dark' : 'light');
  };

  return (
    <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
      <Stack.Screen options={{ headerShown: false }} />
      
      {/* Header */}
      <View style={[styles.header, { borderBottomColor: colors.border }]}>
        <TouchableOpacity 
          onPress={() => router.back()}
          style={styles.backButton}
        >
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <ThemedText style={styles.headerTitle}>Настройки</ThemedText>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
        {/* Section: Preferences */}
        <View style={[styles.section, { backgroundColor: colors.surface, borderColor: colors.border }]}>
          {/* Notification Item */}
          <View style={styles.settingItem}>
            <View style={styles.settingLeft}>
              <View style={[styles.iconContainer, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
                <IconSymbol name="notifications" size={22} color={colors.primaryDark} />
              </View>
              <View style={styles.settingText}>
                <ThemedText style={styles.settingLabel}>Уведомления</ThemedText>
                <ThemedText style={[styles.settingSub, { color: colors.textSub }]}>Статус заказа и акции</ThemedText>
              </View>
            </View>
            <Switch 
              value={notifications} 
              onValueChange={setNotifications}
              trackColor={{ false: '#d1d5db', true: colors.primary }}
              thumbColor="#fff"
              ios_backgroundColor="#d1d5db"
            />
          </View>

          <View style={[styles.divider, { backgroundColor: colors.border }]} />

          {/* Dark Mode Item */}
          <View style={styles.settingItem}>
            <View style={styles.settingLeft}>
              <View style={[styles.iconContainer, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
                <IconSymbol name="moon.fill" size={22} color={colors.primaryDark} />
              </View>
              <View style={styles.settingText}>
                <ThemedText style={styles.settingLabel}>Тёмная тема</ThemedText>
                <ThemedText style={[styles.settingSub, { color: colors.textSub }]}>Оформление приложения</ThemedText>
              </View>
            </View>
            <Switch 
              value={theme === 'dark'} 
              onValueChange={toggleDarkMode}
              trackColor={{ false: '#d1d5db', true: colors.primary }}
              thumbColor="#fff"
              ios_backgroundColor="#d1d5db"
            />
          </View>

          <View style={[styles.divider, { backgroundColor: colors.border }]} />

          {/* Newsletter Item */}
          <View style={styles.settingItem}>
            <View style={styles.settingLeft}>
              <View style={[styles.iconContainer, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
                <IconSymbol name="envelope" size={22} color={colors.primaryDark} />
              </View>
              <View style={styles.settingText}>
                <ThemedText style={styles.settingLabel}>Рассылка новостей</ThemedText>
                <ThemedText style={[styles.settingSub, { color: colors.textSub }]}>Персональные предложения</ThemedText>
              </View>
            </View>
            <Switch 
              value={newsletter} 
              onValueChange={setNewsletter}
              trackColor={{ false: '#d1d5db', true: colors.primary }}
              thumbColor="#fff"
              ios_backgroundColor="#d1d5db"
            />
          </View>
        </View>

        {/* Additional Section: Info */}
        <TouchableOpacity 
          onPress={() => router.push('/support')}
          style={[styles.section, styles.infoSection, { backgroundColor: colors.surface, borderColor: colors.border }]}
        >
          <View style={styles.settingLeft}>
            <View style={[styles.iconContainer, { backgroundColor: theme === 'dark' ? '#374151' : '#f3f4f6' }]}>
              <IconSymbol name="info.circle.fill" size={22} color={colors.textSub} />
            </View>
            <ThemedText style={styles.settingLabel}>Помощь и поддержка</ThemedText>
          </View>
          <IconSymbol name="chevron.right" size={20} color={colors.textSub} />
        </TouchableOpacity>

        {/* Meta/Footer */}
        <View style={styles.footer}>
          <View style={[styles.footerIcon, { backgroundColor: 'rgba(19, 236, 91, 0.1)' }]}>
            <IconSymbol name="leaf.fill" size={32} color={colors.primary} />
          </View>
          <ThemedText style={[styles.appName, { color: colors.textSub }]}>Домашний гастроном</ThemedText>
          <ThemedText style={[styles.version, { color: colors.textSub }]}>Версия 1.0.4</ThemedText>
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
  },
  scrollContent: {
    padding: 16,
    gap: 16,
  },
  section: {
    borderRadius: 20,
    borderWidth: 1,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  infoSection: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    marginTop: 8,
  },
  settingItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    minHeight: 64,
  },
  settingLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
    flex: 1,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  settingText: {
    flex: 1,
  },
  settingLabel: {
    fontSize: 16,
    fontWeight: '600',
  },
  settingSub: {
    fontSize: 12,
    marginTop: 2,
  },
  divider: {
    height: 1,
    marginLeft: 72,
  },
  footer: {
    alignItems: 'center',
    paddingVertical: 40,
    marginTop: 'auto',
  },
  footerIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  appName: {
    fontSize: 14,
    fontWeight: '600',
  },
  version: {
    fontSize: 12,
    marginTop: 4,
  },
});
