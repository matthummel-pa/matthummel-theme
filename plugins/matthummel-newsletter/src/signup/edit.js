import { RichText, useBlockProps } from '@wordpress/block-editor'
import { useInstanceId } from '@wordpress/compose'
import { __ } from '@wordpress/i18n'

export function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps({ className: 'mhn-signup-block' })
  const emailId = useInstanceId(Edit, 'mhn-signup-editor-email')

  return (
    <div {...blockProps}>
      <form
        className="mhn-signup-block__form"
        onSubmit={function (event) {
          event.preventDefault()
        }}
      >
        <RichText
          tagName="h2"
          className="mhn-signup-block__heading"
          value={attributes.heading}
          allowedFormats={[]}
          disableLineBreaks
          placeholder={__('Get updates', 'matthummel-newsletter')}
          onChange={function (heading) {
            setAttributes({ heading })
          }}
        />
        <RichText
          tagName="p"
          className="mhn-signup-block__help"
          value={attributes.description}
          allowedFormats={[]}
          disableLineBreaks
          placeholder={__('I keep the address on this site. I do not send it to a newsletter service.', 'matthummel-newsletter')}
          onChange={function (description) {
            setAttributes({ description })
          }}
        />
        <div className="mhn-signup-block__row">
          <div className="mhn-signup-block__field">
            <label htmlFor={emailId}>{__('Email', 'matthummel-newsletter')}</label>
            <input id={emailId} type="email" disabled placeholder={__('you@example.com', 'matthummel-newsletter')} />
          </div>
          <RichText
            tagName="span"
            className="mhn-signup-block__button"
            value={attributes.buttonText}
            allowedFormats={[]}
            disableLineBreaks
            placeholder={__('Sign up', 'matthummel-newsletter')}
            onChange={function (buttonText) {
              setAttributes({ buttonText })
            }}
          />
        </div>
        <p className="mhn-signup-block__status" role="status" aria-live="polite" />
      </form>
    </div>
  )
}
