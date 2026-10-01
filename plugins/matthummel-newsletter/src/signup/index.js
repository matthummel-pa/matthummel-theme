import { registerBlockType } from '@wordpress/blocks'
import metadata from './block.json'
import { Edit } from './edit'
// Compiles style.scss into build/signup/style-index.css.
import './style.scss'

function save() {
  return null
}

registerBlockType(metadata.name, {
  apiVersion: metadata.apiVersion,
  title: metadata.title,
  category: metadata.category,
  icon: metadata.icon,
  description: metadata.description,
  keywords: metadata.keywords,
  attributes: metadata.attributes,
  supports: metadata.supports,
  textdomain: metadata.textdomain,
  edit: Edit,
  save,
})
