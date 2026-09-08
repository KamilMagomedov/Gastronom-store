import React from 'react';
import { StyleSheet, View, Text, TouchableOpacity, TextInput, ScrollView, Platform, KeyboardAvoidingView, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import { SafeAreaView } from 'react-native-safe-area-context';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { useColorScheme } from '@/hooks/use-color-scheme';
import { Colors } from '@/constants/theme';
import { useCart } from '@/context/cart-context';
import MobileMap from '@/components/MobileMap';
import DeliveryDateTimePicker from '@/components/DeliveryDateTimePicker';
import {ApiService} from '@/services/api';
import { useAuth } from '@/context/auth-context';

export default function CheckoutScreen() {
  const router = useRouter();
  const colorScheme = useColorScheme() ?? 'light';
  const colors = Colors[colorScheme];
  const { clearCart, cart, refresh } = useCart();
  const { user } = useAuth();
  const items = cart?.items ?? [];
  const totalAmount = cart ? parseFloat(cart.total_amount) : 0;
  const totalItemsCount = items.reduce((sum, item) => sum + item.quantity, 0);

  const [city, setCity] = React.useState('');
  const [street, setStreet] = React.useState('');
  const [apartment, setApartment] = React.useState('');
  const [entrance, setEntrance] = React.useState('');
  const [floor, setFloor] = React.useState('');
  const [phone, setPhone] = React.useState('');
  const [comment, setComment] = React.useState('');
  const [debouncedAddress, setDebouncedAddress] = React.useState('');
  const [deliveryDate, setDeliveryDate] = React.useState<Date>(new Date());
  const [deliveryTime, setDeliveryTime] = React.useState<string>('09:00 - 13:00');
  const [settings, setSettings] = React.useState<any>(null);

  const mapUrl = `https://yandex.ru/map-widget/v1/?text=${encodeURIComponent(debouncedAddress)}&z=16`;

  React.useEffect(() => {
    const loadSettings = async () => {
      try {
        const response = await ApiService.getSettings();
        setSettings(response.data);
      } catch (error) {
        console.error('Checkout: Failed to load settings:', error);
      }
    };
    loadSettings();
  }, []);

  React.useEffect(() => {
    if (!city.trim() && !street.trim()) {
      setDebouncedAddress('');
      return;
    }

    const timer = setTimeout(() => {
      const formattedAddress = [city.trim(), street.trim()].filter(Boolean).join(', ');
      setDebouncedAddress(formattedAddress);
    }, 1000);

    return () => clearTimeout(timer);
  }, [city, street]);

  const handleResetOrder = async () => {
    try {
      await clearCart();
      router.replace('/(tabs)');
    } catch (error) {
      console.error('Ошибка при сбросе заказа:', error);
    }
  };

  const handlePay = async () => {
    if (!user?.token) {
      Alert.alert(
        'Необходим вход',
        'Войдите в аккаунт перед оформлением заказа.',
      );
      return;
    }
  
    if (!city.trim()) {
      Alert.alert('Ошибка', 'Введите город.');
      return;
    }
  
    if (!street.trim()) {
      Alert.alert('Ошибка', 'Введите улицу и дом.');
      return;
    }
  
    const cleanPhone = phone.replace(/\D/g, '');
  
    if (cleanPhone.length < 10) {
      Alert.alert('Ошибка', 'Введите корректный номер телефона.');
      return;
    }
  
    if (!settings?.delivery_methods?.length) {
      Alert.alert('Ошибка', 'Не удалось получить способы доставки.');
      return;
    }
  
    if (!settings?.payment_methods?.length) {
      Alert.alert('Ошибка', 'Не удалось получить способы оплаты.');
      return;
    }
  
    try {
      const formattedDate = deliveryDate.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      });
  
      const courierMethod =
        settings.delivery_methods.find((method: any) =>
          method.label?.toLowerCase().includes('курьер'),
        ) ?? settings.delivery_methods[0];
  
      const cashMethod =
        settings.payment_methods.find((method: any) =>
          method.name?.toLowerCase().includes('налич'),
        ) ?? settings.payment_methods[0];
  
      const response = await ApiService.createOrder(
        {
          delivery_method: courierMethod.id,
          payment_method: cashMethod.id,
  
          delivery_phone: `+${cleanPhone}`,
  
          delivery_city: city.trim(),
          delivery_street: street.trim(),
          delivery_apartment: apartment.trim() || undefined,
          delivery_entrance: entrance.trim() || undefined,
          delivery_floor: floor.trim() || undefined,
  
          delivery_notes: comment.trim() || undefined,
  
          notes: [
            `Дата доставки: ${formattedDate}`,
            `Время доставки: ${deliveryTime}`,
          ].join('\n'),
        },
        user.token,
      );
  
      const order = response.data;
  
      await refresh();
  
      router.push({
        pathname: '/order-success',
        params: {
          orderId: String(order.id),
          totalPrice: String(order.total_amount),
          deliveryDate: formattedDate,
          deliveryTime,
          address:
            order.delivery_address ||
            `${city.trim()}, ${street.trim()}`,
        },
      });
    } catch (error: any) {
      console.error('Checkout: create order error:', error);
  
      const validationMessages = error?.errors
        ? Object.values(error.errors).flat().join('\n')
        : null;
  
      Alert.alert(
        'Не удалось оформить заказ',
        validationMessages ||
          error?.message ||
          'Произошла ошибка при создании заказа.',
      );
    }
  };

  const deliveryPrice = settings
  ? (totalAmount >= parseFloat(settings.delivery_settings.free_delivery_threshold) ? 0 : parseFloat(settings.delivery_settings.delivery_fee))
  : 0;

  const totalPrice = totalAmount + deliveryPrice;

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: colors.background }]} edges={['bottom']}>
      <View style={[styles.header, { backgroundColor: colors.surface, borderBottomColor: colors.border }]}>
        <TouchableOpacity onPress={() => router.back()} style={styles.headerButton}>
          <IconSymbol name="chevron.left" size={24} color={colors.text} />
        </TouchableOpacity>
        <Text style={[styles.headerTitle, { color: colors.text }]}>Оформление заказа</Text>
        <TouchableOpacity onPress={handleResetOrder} style={styles.headerButton} hitSlop={10}>
          <Text style={[styles.resetText, { color: colors.primaryDark }]}>Сброс</Text>
        </TouchableOpacity>
      </View>

      <KeyboardAvoidingView 
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={{ flex: 1 }}
      >
        <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
          <View style={styles.section}>
            <View style={styles.sectionHeader}>
              <Text style={[styles.sectionTitle, { color: colors.text }]}>Куда везем?</Text>
            </View>
            {debouncedAddress ? (
              Platform.OS === 'web' ? (
                <iframe
                  src={mapUrl}
                  width="100%"
                  height="150"
                  style={{ border: 0, borderRadius: 16, marginBottom: 16 }}
                  allowFullScreen
                />
              ) : (
                <MobileMap apiKey="d09d333a-47cc-46e9-9f40-ca543b5ff126" />
              )
            ) : (
              <View style={[styles.mapPreview, { backgroundColor: colors.surface, borderRadius: 16, marginBottom: 16, height: 150, justifyContent: 'center', alignItems: 'center' }]}>
                <Text style={{ color: colors.textSub, fontSize: 14 }}>
                  Введите адрес для отображения карты
                </Text>
              </View>
            )}
            
            <View style={styles.inputGroup}>
              <View style={styles.inputWrapper}>
                <Text style={[styles.inputLabel, { color: colors.textSub }]}>Город</Text>
                <TextInput 
                  style={[styles.input, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
                  value={city}
                  onChangeText={setCity}
                  placeholder="Введите город"
                  placeholderTextColor={colors.textSub}
                />
              </View>
              <View style={styles.inputWrapper}>
                <Text style={[styles.inputLabel, { color: colors.textSub }]}>Улица, дом</Text>
                <TextInput 
                  style={[styles.input, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
                  value={street}
                  onChangeText={setStreet}
                  placeholder="Улица, дом, корпус"
                  placeholderTextColor={colors.textSub}
                />
              </View>
              <View style={styles.rowInputs}>
                <View style={[styles.inputWrapper, { flex: 1 }]}>
                  <Text style={[styles.inputLabel, { color: colors.textSub }]}>Кв./Офис</Text>
                  <TextInput 
                    style={[styles.input, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
                    value={apartment}
                    onChangeText={setApartment}
                  />
                </View>
                <View style={[styles.inputWrapper, { flex: 1 }]}>
                  <Text style={[styles.inputLabel, { color: colors.textSub }]}>Подъезд</Text>
                  <TextInput 
                    style={[styles.input, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
                    value={entrance}
                    onChangeText={setEntrance}
                  />
                </View>
                <View style={[styles.inputWrapper, { flex: 1 }]}>
                  <Text style={[styles.inputLabel, { color: colors.textSub }]}>Этаж</Text>
                  <TextInput 
                    style={[styles.input, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
                    value={floor}
                    onChangeText={setFloor}
                  />
                </View>
              </View>
            </View>

            <View style={styles.inputWrapper}>
              <Text style={[styles.inputLabel, { color: colors.textSub }]}>
                Телефон
              </Text>

              <TextInput
                style={[
                  styles.input,
                  {
                    backgroundColor: colors.surface,
                    color: colors.text,
                    borderColor: colors.border,
                  },
                ]}
                value={phone}
                onChangeText={setPhone}
                placeholder="+7 999 123 45 67"
                placeholderTextColor={colors.textSub}
                keyboardType="phone-pad"
              />
            </View>
          </View>

          <DeliveryDateTimePicker
            colors={colors}
            selectedDate={deliveryDate}
            onDateChange={setDeliveryDate}
            selectedTime={deliveryTime}
            onTimeChange={setDeliveryTime}
          />

          <View style={styles.section}>
            <Text style={[styles.sectionTitle, { color: colors.text, marginBottom: 16 }]}>Оплата</Text>
            <View style={styles.paymentOptions}>
              <TouchableOpacity style={[styles.paymentCard, { backgroundColor: colorScheme === 'dark' ? 'rgba(19, 236, 91, 0.15)' : 'rgba(19, 236, 91, 0.1)', borderColor: colors.primary }]}>
                <View style={styles.paymentInfo}>
                  <View style={[styles.paymentIcon, { backgroundColor: colors.surface }]}>
                    <IconSymbol name="creditcard" size={20} color={colors.primaryDark} />
                  </View>
                  <View>
                    <Text style={[styles.paymentTitle, { color: colors.text }]}>Картой онлайн</Text>
                    <Text style={[styles.paymentSub, { color: colors.textSub }]}>Mastercard •••• 4829</Text>
                  </View>
                </View>
                <View style={[styles.radioSelected, { backgroundColor: colors.primary }]} />
              </TouchableOpacity>

              <TouchableOpacity style={[styles.paymentCard, { backgroundColor: colors.surface, borderColor: colors.border }]}>
                <View style={styles.paymentInfo}>
                  <View style={[styles.paymentIcon, { backgroundColor: colors.background }]}>
                    <IconSymbol name="banknote" size={20} color={colors.text} />
                  </View>
                  <View>
                    <Text style={[styles.paymentTitle, { color: colors.text }]}>Наличкой</Text>
                    <Text style={[styles.paymentSub, { color: colors.textSub }]}>При получении</Text>
                  </View>
                </View>
                <View style={[styles.radioUnselected, { borderColor: colors.border }]} />
              </TouchableOpacity>
            </View>
          </View>

          <View style={styles.section}>
            <Text style={[styles.inputLabel, { color: colors.textSub, marginBottom: 8 }]}>Комментарий курьеру</Text>
            <TextInput 
              style={[styles.textArea, { backgroundColor: colors.surface, color: colors.text, borderColor: colors.border }]}
              placeholder="Код домофона, оставить у двери..."
              placeholderTextColor={colors.textSub}
              multiline
              numberOfLines={4}
              value={comment}
              onChangeText={setComment}
            />
          </View>

          <View style={[styles.summary, { borderTopColor: colors.border }]}>
            <View style={styles.summaryRow}>
              <Text style={[styles.summaryText, { color: colors.textSub }]}>Товары ({totalItemsCount})</Text>
              <Text style={[styles.summaryValue, { color: colors.text }]}>{totalAmount.toFixed(0)} ₽</Text>
            </View>
            
            <View style={styles.summaryRow}>
              <Text style={[styles.summaryText, { color: colors.textSub }]}>Доставка</Text>
              <Text style={[styles.summaryValue, { color: deliveryPrice === 0 ? colors.primaryDark : colors.text }]}>
                {deliveryPrice === 0 ? 'Бесплатно' : `${deliveryPrice} ₽`}
              </Text>
            </View>
            
            <View style={styles.summaryRow}>
              <Text style={[styles.summaryText, { color: colors.textSub }]}>Скидка</Text>
              <Text style={[styles.summaryValue, { color: colors.primaryDark }]}>-0 ₽</Text>
            </View>
            
            <View style={[styles.summaryRow, { marginTop: 8 }]}>
              <Text style={[styles.totalText, { color: colors.text }]}>Итого</Text>
              <Text style={[styles.totalValue, { color: colors.text }]}>{totalPrice.toFixed(0)} ₽</Text>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>

      <View style={[styles.footer, { backgroundColor: colors.surface, borderTopColor: colors.border }]}>
        <TouchableOpacity 
          style={[styles.payButton, { backgroundColor: colors.primary }]}
          onPress={handlePay}
        >
          <Text style={styles.payButtonText}>Оплатить {totalPrice.toFixed(0)} ₽</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
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
    paddingVertical: 12,
    borderBottomWidth: 1,
    ...Platform.select({
        ios: { paddingTop: 0 },
        android: { paddingTop: 40 },
    }),
  },
  headerButton: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    flex: 1,
    textAlign: 'center',
  },
  resetText: {
    fontSize: 14,
    fontWeight: '500',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  section: {
    marginBottom: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  sectionTitle: {
    fontSize: 20,
    fontWeight: '700',
  },
  sectionLink: {
    fontSize: 14,
    fontWeight: '600',
  },
  mapPreview: {
    height: 150,
    width: '100%',
    marginBottom: 16,
    justifyContent: 'center',
    alignItems: 'center',
    overflow: 'hidden',
  },
  mapOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.05)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  inputGroup: {
    gap: 12,
  },
  inputWrapper: {
    gap: 6,
  },
  inputLabel: {
    fontSize: 14,
    fontWeight: '500',
  },
  input: {
    height: 48,
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 16,
    fontSize: 16,
  },
  rowInputs: {
    flexDirection: 'row',
    gap: 12,
  },
  calendarBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  dateScroll: {
    gap: 12,
    paddingRight: 16,
  },
  dateCard: {
    width: 88,
    height: 96,
    borderRadius: 16,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dateCardActive: {
    borderWidth: 2,
    elevation: 4,
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
  },
  dateLabel: {
    fontSize: 10,
    fontWeight: '700',
    marginBottom: 2,
  },
  dateValue: {
    fontSize: 24,
    fontWeight: '800',
  },
  dateMonth: {
    fontSize: 12,
    fontWeight: '700',
  },
  paymentOptions: {
    gap: 12,
  },
  paymentCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 16,
    borderRadius: 16,
    borderWidth: 2,
  },
  paymentInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  paymentIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  paymentTitle: {
    fontSize: 14,
    fontWeight: '600',
  },
  paymentSub: {
    fontSize: 12,
  },
  radioSelected: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 5,
    borderColor: '#fff',
  },
  radioUnselected: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 1,
  },
  textArea: {
    height: 96,
    borderWidth: 1,
    borderRadius: 16,
    padding: 16,
    fontSize: 16,
    textAlignVertical: 'top',
  },
  summary: {
    borderTopWidth: 1,
    paddingTop: 16,
    gap: 8,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  summaryText: {
    fontSize: 14,
  },
  summaryValue: {
    fontSize: 14,
    fontWeight: '500',
  },
  totalText: {
    fontSize: 18,
    fontWeight: '700',
  },
  totalValue: {
    fontSize: 20,
    fontWeight: '700',
  },
  footer: {
    padding: 16,
    paddingBottom: Platform.OS === 'ios' ? 34 : 16,
    borderTopWidth: 1,
  },
  payButton: {
    height: 56,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#13ec5b',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  payButtonText: {
    color: '#102216',
    fontSize: 18,
    fontWeight: '700',
  },
});
