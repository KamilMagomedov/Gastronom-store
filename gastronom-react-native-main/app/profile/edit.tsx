import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { Stack, useRouter } from 'expo-router';
import { ThemedView } from '@/components/themed-view';
import { ThemedText } from '@/components/themed-text';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { useAuth } from '@/context/auth-context';
import {
  ApiError,
  ApiService,
  UpdateCustomerProfileData,
} from '@/services/api';

export default function EditProfileScreen() {
  const router = useRouter();
  const insets = useSafeAreaInsets();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];

  const { user } = useAuth();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');

  const [city, setCity] = useState('');
  const [street, setStreet] = useState('');
  const [building, setBuilding] = useState('');
  const [apartment, setApartment] = useState('');
  const [entrance, setEntrance] = useState('');
  const [floor, setFloor] = useState('');
  const [postalCode, setPostalCode] = useState('');

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!user?.token) {
      setLoading(false);
      return;
    }

    let mounted = true;

    const loadProfile = async () => {
      try {
        setLoading(true);
        setError(null);

        const response = await ApiService.getProfile(
          user.token,
        );

        if (!mounted) {
          return;
        }

        const profile = response.data;

        setName(profile.name ?? '');
        setEmail(profile.email ?? '');
        setPhone(profile.phone ?? '');

        setCity(profile.delivery_city ?? '');
        setStreet(profile.delivery_street ?? '');
        setBuilding(profile.delivery_building ?? '');
        setApartment(profile.delivery_apartment ?? '');
        setEntrance(profile.delivery_entrance ?? '');
        setFloor(profile.delivery_floor ?? '');
        setPostalCode(profile.delivery_postal_code ?? '');
      } catch (loadError) {
        console.error(
          'Edit profile: failed to load profile',
          loadError,
        );

        setError('Не удалось загрузить данные профиля.');
      } finally {
        if (mounted) {
          setLoading(false);
        }
      }
    };

    loadProfile();

    return () => {
      mounted = false;
    };
  }, [user?.token]);

  const handleSave = async () => {
    if (!user?.token || saving) {
      return;
    }

    const trimmedName = name.trim();
    const trimmedEmail = email.trim();
    const normalizedPhone = phone
      .trim()
      .replace(/[^\d+]/g, '');

    if (!trimmedName) {
      setError('Введите имя.');
      return;
    }

    if (!trimmedEmail) {
      setError('Введите email.');
      return;
    }

    if (
      normalizedPhone &&
      !/^(\+7|7|8)[0-9]{10}$/.test(normalizedPhone)
    ) {
      setError(
        'Введите телефон в формате +79281234567.',
      );
      return;
    }

    const data: UpdateCustomerProfileData = {
      name: trimmedName,
      email: trimmedEmail,
      phone: normalizedPhone || null,
      delivery_city: city.trim() || null,
      delivery_street: street.trim() || null,
      delivery_building: building.trim() || null,
      delivery_apartment: apartment.trim() || null,
      delivery_entrance: entrance.trim() || null,
      delivery_floor: floor.trim() || null,
      delivery_postal_code: postalCode.trim() || null,
    };

    try {
      setSaving(true);
      setError(null);

      await ApiService.updateProfile(
        data,
        user.token,
      );

      router.back();
    } catch (saveError) {
      console.error(
        'Edit profile: failed to update profile',
        saveError,
      );

      const apiError = saveError as ApiError;

      setError(
        apiError?.message ||
          'Не удалось сохранить изменения.',
      );
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <ThemedView
        style={[
          styles.container,
          {
            paddingTop: insets.top,
            alignItems: 'center',
            justifyContent: 'center',
          },
        ]}
      >
        <ActivityIndicator
          size="large"
          color={colors.primary}
        />
      </ThemedView>
    );
  }

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={{ flex: 1 }}
    >
      <ThemedView style={[styles.container, { paddingTop: insets.top }]}>
        <Stack.Screen options={{ headerShown: false }} />

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
          <View style={styles.avatarSection}>
            <View
              style={[
                styles.profileInitialCircle,
                {
                  borderColor: colors.primary,
                  backgroundColor: colors.surface,
                },
              ]}
            >
              <ThemedText style={styles.profileInitial}>
                {name.trim().charAt(0).toUpperCase() || '?'}
              </ThemedText>
            </View>

            <ThemedText style={styles.profileName}>
              {name || 'Пользователь'}
            </ThemedText>
          </View>

          <View style={styles.form}>
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

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Город
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={city}
                  onChangeText={setCity}
                  placeholder="Город"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Улица
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={street}
                  onChangeText={setStreet}
                  placeholder="Улица"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Дом
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={building}
                  onChangeText={setBuilding}
                  placeholder="Дом"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Квартира
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={apartment}
                  onChangeText={setApartment}
                  placeholder="Квартира"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Подъезд
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={entrance}
                  onChangeText={setEntrance}
                  placeholder="Подъезд"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Этаж
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={floor}
                  onChangeText={setFloor}
                  placeholder="Этаж"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>

            <View style={styles.inputContainer}>
              <ThemedText style={styles.label}>
                Индекс
              </ThemedText>

              <View
                style={[
                  styles.inputWrapper,
                  {
                    backgroundColor: colors.surface,
                    borderColor: colors.border,
                  },
                ]}
              >
                <IconSymbol
                  name="mappin"
                  size={20}
                  color={colors.textSub}
                  style={styles.inputIcon}
                />

                <TextInput
                  style={[
                    styles.input,
                    { color: colors.text },
                  ]}
                  value={postalCode}
                  onChangeText={setPostalCode}
                  placeholder="Индекс"
                  placeholderTextColor={colors.textSub}
                />
              </View>
            </View>
          </View>

          <View style={{ height: 120 }} />
        </ScrollView>

        {error ? (
          <ThemedText style={styles.errorText}>
            {error}
          </ThemedText>
        ) : null}

        <View style={[styles.footer, {
          paddingBottom: Math.max(insets.bottom, 16),
          backgroundColor: colors.background,
          }]}>
          <TouchableOpacity
            style={[
              styles.saveButton,
              {
                backgroundColor: colors.primary,
                opacity: saving ? 0.6 : 1,
              },
            ]}
            onPress={handleSave}
            disabled={saving}
          >
            {saving ? (
              <ActivityIndicator
                size="small"
                color="#102216"
              />
            ) : (
              <ThemedText style={styles.saveButtonText}>
                Сохранить изменения
              </ThemedText>
            )}
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
  profileInitialCircle: {
    width: 120,
    height: 120,
    borderRadius: 60,
    borderWidth: 3,
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileInitial: {
    fontSize: 42,
    fontWeight: '800',
  },
  profileName: {
    fontSize: 24,
    fontWeight: '800',
    marginTop: 16,
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
  errorText: {
    color: '#dc2626',
    fontSize: 13,
    textAlign: 'center',
    marginHorizontal: 20,
    marginBottom: 8,
  },
});
