(function () {
  if (typeof tinymce === 'undefined' || !tinymce.PluginManager) return

  tinymce.PluginManager.add('mhn_letter', function (editor) {
    editor.addButton('mhn_button', {
      text: 'Button',
      icon: false,
      tooltip: 'Insert a button',
      onclick: function () {
        editor.windowManager.open({
          title: 'Button',
          body: [
            { type: 'textbox', name: 'label', label: 'Label' },
            { type: 'textbox', name: 'url', label: 'Link' },
          ],
          onsubmit: function (event) {
            const label = String(event.data.label || '').trim()
            const url = String(event.data.url || '').trim()
            if (label === '' || url === '' || /^(javascript|data|vbscript):/i.test(url)) return
            const href = editor.dom.encode(url)
            const text = editor.dom.encode(label)
            editor.insertContent('<p><a class="mhn-email-button" href="' + href + '">' + text + '</a></p>')
          },
        })
      },
    })

    editor.addButton('mhn_merge', {
      type: 'menubutton',
      text: 'Merge tag',
      icon: false,
      tooltip: 'Insert a name',
      menu: [
        { text: '{first_name|there}', onclick: function () { editor.insertContent('{first_name|there}') } },
        { text: '{first_name}', onclick: function () { editor.insertContent('{first_name}') } },
        { text: '{last_name}', onclick: function () { editor.insertContent('{last_name}') } },
        { text: '{full_name}', onclick: function () { editor.insertContent('{full_name}') } },
      ],
    })
  })
})()
