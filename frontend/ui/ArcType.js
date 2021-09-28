import { View } from 'curvature/base/View';
import { Gamepad } from 'curvature/input/Gamepad';

export class ArcType extends View
{
	template = require('./arc-type.html');

	constructor(args, parent)
	{
		super(args, parent);

		const lower  = [...Array(26)].map((v, k) => String.fromCharCode('a'.charCodeAt(0) + k));

		const upper  = [...Array(26)].map((v, k) => String.fromCharCode('A'.charCodeAt(0) + k));
		const number = [...Array(10)].map((v, k) => String.fromCharCode('0'.charCodeAt(0) + k));

		const extraA = '.,"@:/'.split('');
		const extraB = '!?-&;\\'.split('');
		const extraC = '`*\'"-=[]()~:{}<>^|%$#+'.split('');

		const banks = [
			[...lower, ...extraA]
			, [...upper, ...extraB]
			, [...number, ...extraC]
		];

		this.letters = banks[0];

		this.args.angle = 'x';

		document.addEventListener('keydown', event => {
			if(event.key === 'Enter')
			{
				this.deactivate();
			}

			if(event.key === 'Escape')
			{
				this.deactivate(false);
			}
		});

		Gamepad.getPad({deadZone:0.2}).then(pad => {

			const eachFrame = () => {

				pad.readInput();

				if(pad.buttons[4].active && pad.buttons[4].delta)
				{
					this.tags.buffer.focus()
					document.execCommand('delete', false);
				}

				if(pad.buttons[5].active && pad.buttons[5].delta)
				{
					this.tags.buffer.focus()
					document.execCommand('insertText', false, ' ');
				}

				if(pad.buttons[6].active)
				{
					this.letters = banks[1];
				}
				else if(pad.buttons[7].active)
				{
					this.letters = banks[2];
				}
				else
				{
					this.letters = banks[0];
				}

				if(pad.buttons[8].active)
				{
					this.deactivate();
				}

				this.setButtons();

				const angle = Math.atan2(pad.axes[1].magnitude, pad.axes[0].magnitude) / Math.PI;

				let selected = (6 + (Math.round(angle * 4) + 4)) % 8;

				this.args.angle = selected;

				if(!pad.axes[0].magnitude && !pad.axes[1].magnitude)
				{
					this.args.angle = 'x';
					selected = undefined;
				}

				const sectors = this.findTags('.arctype-wing');

				for(const i in sectors)
				{
					const sector = sectors[i];

					if(i !== undefined && Number(i) === selected)
					{
						sector.classList.add('arctype-selected');

						for(const i of [...Array(4)].keys())
						{
							if(pad.buttons[i].active && pad.buttons[i].delta)
							{
								sector.querySelector(`.arctype-button:nth-child(${i+1})`).click();
							}
						}
					}
					else
					{
						sector.classList.remove('arctype-selected');
					}
				}

				requestAnimationFrame(eachFrame);

			};

			requestAnimationFrame(eachFrame);

		});
	}

	onAttach()
	{
		this.setButtons();
	}

	setButtons()
	{
		this.buttons = this.buttons || this.findTags('.arctype-button');

		for(const i in this.buttons)
		{
			const button = this.buttons[i];

			button.dataset.buttonId = i;

			if(i in this.letters)
			{
				button.querySelector('span').innerText = this.letters[i];
			}
		}
	}

	click(event)
	{
		event && event.preventDefault();

		if(!('buttonId' in event.currentTarget.dataset))
		{
			return;
		}

		const buttonId = Number(event.currentTarget.dataset.buttonId);

		this.tags.buffer.focus();

		if(buttonId in this.letters)
		{
			const letter = this.letters[buttonId];

			document.execCommand('insertText', false, letter);
		}
	}

	activate(target)
	{
		if(this.args.active || this.tags.buffer.isSameNode(target))
		{
			return;
		}

		this.tags.buffer.focus();

		console.log(target.value);

		this.tags.buffer.node.value = target.value;
		this.args.active = true;
		this.target      = target;

		this.tags.buffer.value = '';
	}

	deactivate(setValue = true)
	{
		if(setValue)
		{
			this.target.value = this.tags.buffer.value;
		}

		this.args.active  = false;
	}
}
