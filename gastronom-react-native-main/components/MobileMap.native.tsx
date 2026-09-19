import React from 'react';
import { Text, View } from 'react-native';
import { Yamap, YamapInstance } from 'react-native-yamap-plus';

const apiKey = process.env.EXPO_PUBLIC_YANDEX_MAPS_API_KEY;

export default function MobileMap() {
  React.useEffect(() => {
    if (!apiKey) {
      console.error('EXPO_PUBLIC_YANDEX_MAPS_API_KEY is not configured');
      return;
    }

    YamapInstance.init(apiKey);
  }, []);

  if (!apiKey) {
    return (
      <View
        style={{
          height: 150,
          width: '100%',
          borderRadius: 16,
          overflow: 'hidden',
          marginBottom: 16,
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Text>Карта недоступна</Text>
      </View>
    );
  }

  return (
    <View
      style={{
        height: 150,
        width: '100%',
        borderRadius: 16,
        overflow: 'hidden',
        marginBottom: 16,
      }}
    >
      <Yamap style={{ flex: 1 }} showUserPosition={true} />
    </View>
  );
}
