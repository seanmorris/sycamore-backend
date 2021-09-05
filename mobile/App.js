import React                     from 'react';
import { Platform }              from 'react-native';
import { Text, View, Vibration } from 'react-native';
import { Keyboard, Dimensions }  from 'react-native';
import { NativeModules }         from 'react-native';

const { StatusBarManager } = NativeModules;

import * as Permissions  from 'expo-permissions';
import * as SplashScreen from 'expo-splash-screen';
// import * as Notifications from 'expo-notifications';

import NetInfo from '@react-native-community/netinfo';
import * as FileSystem from 'expo-file-system';

import { StatusBar } from 'react-native';
import { KeyboardAvoidingView } from 'react-native';

import * as SecureStore from 'expo-secure-store';

import WebTemplate from './WebTemplate';

let origin = 'https://sycamore.seanmorr.is/';

if(__DEV__)
{
	// origin = 'https://beta.sycamore.seanmorr.is';
}

// const gcmSenderId = '540XXXXXXXXX';
const localUri    = require('./assets/app.html');

export default class App extends React.Component
{
	constructor()
	{
		super();

		this.state = this.state || {
			baseUrl:       'file://',
			isSplashReady: true,
			isAppReady:    true,
		};

		this.network = false;
		this.offlineSource = null;

		this.wasConencted = false;
		this.currentUser  = false;
	}

	render()
	{
		const sourceState = {
			uri:     this.state.url,
			html:    this.state.html,
			baseUrl: this.state.baseUrl
		};

		return WebTemplate(this, sourceState);
	}

	componentDidMount()
	{
		this.setState({baseUrl: origin , url: origin});
	}

	handleLoadStart(sourceState)
	{
		// this.webView.injectJavaScript(Platform.select({
		// 	android: 'window.Android = true;'
		// 	, ios:   'window.iOS = true;'
		// }));
	}

	handleLoadEnd(event)
	{
		// this.webView.injectJavaScript(Platform.select({
		// 	android: 'window.Android = true;'
		// 	, ios:   'window.iOS = true;'
		// }));

		if(this.state.isAppReady)
		{
			return;
		}

		this.state.isAppReady = true;
	}
}
