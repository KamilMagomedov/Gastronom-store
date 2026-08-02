import React, { useState } from 'react';
import { StyleSheet, TouchableOpacity, ScrollView, View, Image, TextInput, KeyboardAvoidingView, Platform } from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export default function EditProfileScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const [name, setName] = useState('Александр Петров');
  const [email, setEmail] = useState('alex.petrov@example.com');
  const [phone, setPhone] = useState('+7 (999) 123-45-67');
  const [address, setAddress] = useState('Москва, ул. Тверская, д. 12, кв. 45');

  return (
    <KeyboardAvoidingView 
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={{ flex: 1 }}
    >
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
          <ThemedText style={styles.headerTitle}>Редактирование</ThemedText>
          <TouchableOpacity 
            onPress={() => router.back()}
            style={styles.cancelButton}
          >
            <ThemedText style={[styles.cancelText, { color: colors.textSub }]}>Отмена</ThemedText>
          </TouchableOpacity>
        </View>

        <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={styles.scrollContent}>
          {/* Profile Header with Avatar */}
          <View style={styles.avatarSection}>
            <View style={styles.avatarWrapper}>
              <Image 
                source={{ uri: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCIjh7_-rwypcsFwdkOkAh4cQY3sGW0i3GG6Iu9SaOnKkuAKHJ-eS5wyyoqeAPlDzu_RBpaUiV0uDzOlOIK_j05Plwhtsj3kZc7tJ45VOmYR84Rf7nGy3relhlS_-bHDnKacbM3Oi1yKTgEwsQWydD86KJAEQtxeAcWivs1eH_L2AdjEN1HyDewfqkpOGy0st2m0tNV4mX_V4QXmrSqaUab9vn9kwb3DLRS56Qovgb9Kkricw1ru5XnLr3UG_o3Gx3gqFTxuu8F3xM' }} 
                style={styles.avatar} 
              />
              <View style={[styles.editBadge, { backgroundColor: colors.primary, borderColor: colors.background }]}>
                <IconSymbol name="camera.fill" size={18} color="#000" />
              </View>
            </View>
            <ThemedText style={styles.profileName}>{name}</ThemedText>
            <ThemedText style={[styles.changePhotoText, { color: colors.textSub }]}>Изменить фото профиля</ThemedText>
          </View>

          {/* Form Fields */}
          <View style={styles.form}>
            {/* Name Field */}
            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>Имя и Фамилия</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <IconSymbol name="person" size={20} color={colors.textSub} style={styles.inputIcon} />
                <TextInput 
                  style={[styles.input, { color: colors.text }]}
                  value={name}
                  onChangeText={setName}
                  placeholder="Введите имя"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            {/* Email Field */}
            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>Email</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <IconSymbol name="envelope" size={20} color={colors.textSub} style={styles.inputIcon} />
                <TextInput 
                  style={[styles.input, { color: colors.text }]}
                  value={email}
                  onChangeText={setEmail}
                  placeholder="name@example.com"
                  placeholderTextColor={colors.textSub}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>
            </View>

            {/* Phone Field */}
            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>Телефон</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <IconSymbol name="phone" size={20} color={colors.textSub} style={styles.inputIcon} />
                <TextInput 
                  style={[styles.input, { color: colors.text }]}
                  value={phone}
                  onChangeText={setPhone}
                  placeholder="+7 (___) ___-__-__"
                  placeholderTextColor={colors.textSub}
                  keyboardType="phone-pad"
                />
              </View>
            </View>

            {/* Address Field */}
            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>Адрес доставки</ThemedText>
              <View style={[styles.inputWrapper, { backgroundColor: colors.surface, borderColor: colors.border, alignItems: 'flex-start', minHeight: 100 }]}>
                <IconSymbol name="mappin" size={20} color={colors.textSub} style={[styles.inputIcon, { marginTop: 14 }]} />
                <TextInput 
                  style={[styles.input, { color: colors.text, height: '100%', paddingTop: 14 }]}
                  value={address}
                  onChangeText={setAddress}
                  placeholder="Город, улица, дом, квартира"
                  placeholderTextColor={colors.textSub}
                  multiline
                  textAlignVertical="top"
                />
              </View>
            </View>
          </View>
          
          <View style={{ height: 120 }} />
        </ScrollView>

        {/* Footer */}
        <View style={[styles.footer, { paddingBottom: Math.max(insets.bottom, 16) }]}>
          <TouchableOpacity 
            style={[styles.saveButton, { backgroundColor: colors.primary }]}
            onPress={() => router.back()}
          >
            <ThemedText style={styles.saveButtonText}>Сохранить изменения</ThemedText>
          </TouchableOpacity>
        </View>
      </ThemedView>
    </KeyboardAvoidingView>
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
  cancelButton: {
    paddingHorizontal: 8,
  },
  cancelText: {
    fontSize: 16,
    fontWeight: '700',
  },
  scrollContent: {
    paddingBottom: 20,
  },
  avatarSection: {
    alignItems: 'center',
    paddingVertical: 32,
  },
  avatarWrapper: {
    position: 'relative',
  },
  avatar: {
    width: 120,
    height: 120,
    borderRadius: 60,
  },
  editBadge: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 36,
    height: 36,
    borderRadius: 18,
    borderWidth: 4,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileName: {
    fontSize: 24,
    fontWeight: '800',
    marginTop: 16,
  },
  changePhotoText: {
    fontSize: 14,
    marginTop: 4,
  },
  form: {
    paddingHorizontal: 20,
    gap: 20,
  },
  inputContainer: {
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
    borderWidth: 1,
    borderRadius: 16,
    height: 56,
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
  footer: {
    padding: 20,
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  saveButton: {
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 10,
    elevation: 6,
  },
  saveButtonText: {
    color: '#102216',
    fontSize: 18,
    fontWeight: '800',
  },
});
