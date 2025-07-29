import  { useState, useEffect, forwardRef } from 'react'

/**
 * SearchInput component with debounced and immediate change handlers.
 * @param {object} props
 * @param {string} [props.placeholder] - Placeholder text for the input.
 * @param {string} props.value - Controlled value for the input.
 * @param {function} props.onChange - Handler for immediate value changes.
 * @param {function} props.onDebouncedChange - Handler for debounced value changes.
 * @param {React.Ref} ref - Ref for the input element.
 */
const SearchInput = forwardRef(function SearchInput(
  { placeholder = 'Search...', value, onChange, onDebouncedChange },
  ref
) {
  const [localValue, setLocalValue] = useState(value || '')

  // Keep localValue in sync with value prop
  useEffect(() => {
    setLocalValue(value || '')
  }, [value])

  // Debounce value changes
  useEffect(() => {
    const handler = setTimeout(() => {
      if (onDebouncedChange) {
        onDebouncedChange(localValue)
      }
    }, 300)
    return () => clearTimeout(handler)
  }, [localValue, onDebouncedChange])

  const handleClear = () => {
    setLocalValue('')
    if (onChange) onChange({ target: { value: '' } })
    if (onDebouncedChange) onDebouncedChange('')
    if (ref && typeof ref === 'object' && ref.current) {
      ref.current.focus()
    }
  }

  return (
    <div className="w-64 relative">
      <input
        ref={ref}
        type="text"
        placeholder={placeholder}
        className="w-full px-4 py-2 pr-8 rounded-md border border-telescope-border focus:outline-none focus:ring-2 focus:ring-indigo-500"
        value={localValue}
        onChange={e => {
          setLocalValue(e.target.value)
          if (onChange) onChange(e)
        }}
        aria-label={placeholder}
      />
      {localValue && (
        <button
          type="button"
          aria-label="Clear search"
          onClick={handleClear}
          className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
          tabIndex={0}
        >
          &#10005;
        </button>
      )}
    </div>
  )
})

export default SearchInput