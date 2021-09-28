import { View } from 'curvature/base/View';
import { Tag } from 'curvature/base/Tag';

import { ArcType } from '../ui/ArcType';

import CodeMirror from 'codemirror';
import 'codemirror/mode/javascript/javascript';

require('codemirror/addon/lint/lint');
require('codemirror/addon/lint/json-lint');

export class Editor extends View
{
	constructor()
	{
		super();

		this.template = require('./editor.html');

		this.dataSources = [
			{persons: 'https://randomuser.me/api/?results=50#results'}
		];

		// this.args.arc = new ArcType;

		this.args.selectPath = [];

		this.args.selections = [];
		this.args.selected   = false;
		this.editorArgs      = {};

		this.args.previewHtml = true;
		this.args.editData = false;
		this.args.editText = true;
		this.args.editCss  = true;
		this.args.editJs   = true;
		this.args.wysiwyg  = true;

		this.args.bgColor = '#FFFFFF';
		this.args.fgColor = '#000000';

		this.args.fonts = [
			'Arial'
			, 'Courier'
			, 'Courier New'
			, 'Helvetica'
			, 'Times New Roman'
			, 'Palatino'
			, 'Garamond'
			, 'Bookman'
			, 'Avant Garde'
			, 'monospace'
			, 'serif'
			, 'sans-serif'
		];
	}

	toggle(event, name)
	{
		this.args[name] = this.args[name] ? false : name;

		if(name === 'wysiwyg')
		{
			this.blur();
		}
	}

	onAttach()
	{
		this.sourceEditor.refresh();
	}

	postRender()
	{
		this.args.source = `<h1>[[title]]</h1>
<p>[[tagline]]</p>
<p><img cv-expand = "image" src = "/player-head.png" /></p>
<p>Here is a list of people from <a target = "blank" href = "https://randomuser.me/">randomuser.me</a></p>
<ul cv-each = "persons:person:p">
	<li>[[person.name.first]] [[person.name.last]]</li>
</ul>
`;
		const sourceEditor = this.newEditor();

		this.args.bindTo(
			'source'
			, v => {
				this.args.vv = View.from(v, this.editorArgs, this)

				if(v === sourceEditor.getValue())
				{
					return;
				}

				sourceEditor.setValue(v);
				sourceEditor.refresh();
			}
		);

		sourceEditor.on('change', (editor, change) => {
			this.args.source = editor.getValue();
		});

		this.args.sourceEditor = new Tag(sourceEditor.getWrapperElement());
		this.sourceEditor = sourceEditor;

		this.args.sourceEditor.addEventListener(
			'cvDomAttached'
			, event => {
				sourceEditor.refresh();
				this.format();
			}
		);

		const dataEditor = this.newEditor();

		dataEditor.on('beforeChange', (editor, change) =>
			this.beforeEdit = dataEditor.getValue()
		);

		dataEditor.on('change', (editor, change) => {
			const current = dataEditor.getValue();
			const input   = {};

			try { Object.assign(input, JSON.parse(current || '{}')) }
			catch(error){ console.warn(error) }

			Object.assign(this.args.vv.args, input);
		});

		this.dataEditor = dataEditor;

		this.args.dataEditor = dataEditor.display.wrapper;

		this.args.dataEditor.addEventListener(
			'cvDomAttached'
			, event => dataEditor.refresh()
		);

		this.sourceData = {
			title: 'Hello, world!'
			, tagline: 'this is a quad-bound HTML editor!'
			, image:  {
				width:    180
				, style:  'image-rendering: pixelated'
			}
			, persons: []
		};

		dataEditor.setValue(JSON.stringify(this.sourceData, null, 4));

		this.blur(event);

		this.args.jsonUrl = 'https://randomuser.me/api/?results=10'

		fetch(this.args.jsonUrl).then(r=>r.json()).then(r=>{

			if(!r.results)
			{
				return;
			}

			this.sourceData.persons = r.results;

			dataEditor.setValue(JSON.stringify(this.sourceData, null, 4));
		});
	}

	blur()
	{
	}

	format()
	{
		const element = this.tags.html.element;

		const nodes = [...element.childNodes].filter(node=>{
			return node.length > 0 || node.nodeType !== node.TEXT_NODE
		});

		this.sourceEditor.setValue(this.formatNodes(nodes, 0));
	}

	formatNodes(nodes, depth = 0)
	{
		const indent = ' '.repeat(depth * 4);

		const formatted = [];

		for(const i in nodes)
		{
			const node = nodes[i];

			let line;

			if(node.hasChildNodes())
			{
				const open  = node.cloneNode(false).outerHTML.replace(/\<\/.+/, '');
				const close = `</${node.tagName.toLowerCase()}>`;

				let child = this.formatNodes([...node.childNodes], depth+1);

				if(node.querySelector('*'))
				{
					const style = node.childNodes[0] instanceof Element
						? window.getComputedStyle(node.childNodes[0])
						: {};

					if(node.childNodes.length > 1 && style.display !== 'inline')
					{
						child = "\n" + "\t".repeat((depth + 1))
						+ child.trim()
						+ "\n" + "\t".repeat((depth + 0));
					}
					else
					{
						child = child.trim();
					}
				}
				else
				{
					child = child.trim();
				}

				line = open + child + close;
			}
			else
			{
				line = String(node.outerHTML || node.textContent).trim();
			}

			if(line)
			{
				formatted.push(indent + line + "\n");
			}
		}

		return formatted.join('');
	}

	click(event)
	{
		const target = event
			? event.target
			: this.args.selected;

		if(!this.tags.html.contains(target))
		{
			return;
		}

		if(!this.tags.html.contains(target)
			|| this.tags.html.isSameNode(target)
			|| (this.args.selected && target.isSameNode(this.args.selected))
		){
			event && event.preventDefault();

			for(const key of Object.keys(this.args.selections))
			{
				delete this.args.selections[key];
			}
			this.args.selected = false;
			return;
		}

		let parent = target.parentNode;

		this.args.selectPath = [];

		while(!this.tags.html.isSameNode(parent) && !document.body.isSameNode(parent))
		{
			this.args.selectPath.push(String(parent.tagName).toLowerCase())
			parent = parent.parentNode;
		}

		this.args.selected = target;

		this.args.tagName = String(target.tagName).toLowerCase();

		delete this.args.selections[0];

		this.onNextFrame(()=>{
			this.args.selections[0] = {
				left:   target.offsetLeft,
				top:    target.offsetTop,
				width:  target.clientWidth,
				height: target.clientHeight
			};

			this.args.attributes = [];

			for(let i = 0; i < target.attributes.length; i++)
			{
				const attrControl = {
					value:   target.attributes[i].value
					, name:  target.attributes[i].name
					, index: i
				};

				this.args.attributes.push(attrControl);

				attrControl.bindTo('value', v => {
					target.attributes[i].value = v;
					this.format();
				});
			}
		});
	}

	newAttribute()
	{
		this.args.adding = true;
	}

	addAttribute()
	{
		this.args.adding = false;

		this.args.selected.setAttribute(this.args.newAttrName, this.args.newAttrValue);
		this.format();
		this.click();
	}

	removeAttr({name})
	{
		this.args.selected.removeAttribute(name);
		this.click();
	}

	newEditor(textbox)
	{
		textbox = textbox || new Tag(`<textarea>`);

		const editor = CodeMirror(textbox, {
			theme:        'elegant'
			, autoRefresh: true
			, mode:        'application/json'
		});

		this.onNextFrame(()=> editor.refresh());

		return editor;
	}

	loadJson(event)
	{
		event.preventDefault();

		fetch(this.args.jsonUrl).then(r=>r.json()).then(r=>{

			if(!r.results)
			{
				return;
			}

			this.onTimeout(250, () => {

				this.editorArgs.persons = r.results;

				this.dataEditor.setValue(JSON.stringify(this.editorArgs, null, 4));

				this.blur(event);
			});
		});
	}

	execCommand(event, name)
	{
		event.preventDefault();
		event.stopPropagation();
		document.execCommand(name);
	}

	setFont(event, font)
	{
		document.execCommand('fontName', false, font);
	}

	setColor(event, type)
	{
		const selection = window.getSelection();

		const command = type === 'fg'
			? 'foreColor'
			: 'hiliteColor';

		document.execCommand(command, false, event.target.value);
	}

	clear(event)
	{
		event.target.value = '';
	}
}
