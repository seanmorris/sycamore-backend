import React from 'react';
import { View } from 'react-native';
import { StyleSheet  } from 'react-native';

import { WebView } from 'react-native-webview';

export default (parent, sourceState) => {
	return <View style={{ flex: 1, backgroundColor: 'black' }}>
		<WebView
			ref={r => parent.webView = r}
			source={sourceState}
			useWebKit={true}
			originWhitelist={['*']}
			mixedContentMode={'always'}
			domStorageEnabled={true}
			javaScriptEnabled={true}
			startInLoadingState={true}

			userAgent={'Sycamore Native'}

			bounces={false}
			scrollEnabled={false}
			scalesPageToFit={true}

			allowsInlineMediaPlayback={true}
			mediaPlaybackRequiresUserAction={false}
			automaticallyAdjustContentInsets={false}
			keyboardDisplayRequiresUserAction={true}

			style={{backgroundColor: 'transparent', flex: 1}}
			contentContainerStyle={{ flex: 1 }}

			renderError={event=>console.log(event)}
			// onLoadStart={event=>parent.handleLoadStart(event)}
			// onLoadEnd={event=>parent.handleLoadEnd(event)}
			// onMessage={event=>parent.handleMessage(event)}
			/>
		</View>
};
