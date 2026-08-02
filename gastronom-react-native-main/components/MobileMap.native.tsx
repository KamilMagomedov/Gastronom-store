import React from 'react';
import { View } from 'react-native';
import { Yamap, YamapInstance } from 'react-native-yamap-plus';

interface MobileMapProps {
  apiKey: string;
}

export default function MobileMap({ apiKey }: MobileMapProps) {
  React.useEffect(() => {
    YamapInstance.init(apiKey);
  }, [apiKey]);

  return (
    <View style={{ height: 150, width: '100%', borderRadius: 16, overflow: 'hidden', marginBottom: 16 }}>
      <Yamap
        style={{ flex: 1 }}
        showUserPosition={true}
      />
    </View>
  );
}