import  { useState, useEffect, useRef } from 'react'
import { format, formatDistanceToNow } from 'date-fns'
import Prism from 'prismjs'
import 'prismjs/components/prism-sql'
import 'prismjs/themes/prism-tomorrow.css'

/**
 * Utility to get the file name from a path.
 * @param {string} path
 * @returns {string}
 */
function getFileName(path) {
  if (!path) return ''
  return path.split('/').pop()
}

/**
 * Utility to check if parameters are empty.
 * @param {any} params
 * @returns {boolean}
 */
function isEmptyParameters(params) {
  if (!params) return true
  if (Array.isArray(params)) return params.length === 0
  if (typeof params === 'object') return Object.keys(params).length === 0
  return !params
}

/**
 * QueryParameters displays query parameters if present.
 * @param {object} props
 * @param {any} props.parameters
 */
function QueryParameters({ parameters }) {
  if (isEmptyParameters(parameters)) return null
  return (
    <div className="bg-telescope-code-bg rounded-lg p-4 mt-2 overflow-x-auto">
      <div className="text-sm text-gray-500 mb-1 text-right">Query Params</div>
      <pre className="font-mono text-telescope-code-text text-sm whitespace-pre-wrap">
        {Array.isArray(parameters)
          ? JSON.stringify(parameters, null, 2)
          : typeof parameters === 'object'
            ? JSON.stringify(parameters, null, 2)
            : String(parameters)}
      </pre>
    </div>
  )
}

/**
 * QueryDetails displays details for a single query, including SQL, metadata, and backtrace.
 * @param {object} props
 * @param {object} props.query
 */
function QueryDetails({ query, timeDisplayMode = 'absolute' }) {
  const sqlRef = useRef(null)
  const [showAll, setShowAll] = useState(false)
  const [copied, setCopied] = useState(false)
  const trace = Array.isArray(query.backtraces) ? query.backtraces : []
  const driver = query.connection_raw?.driver || 'unknown'
  const database = query.connection_raw?.database || 'unknown'

  // Highlight SQL on query change
  useEffect(() => {
    if (sqlRef.current) {
      Prism.highlightElement(sqlRef.current)
    }
  }, [query.display_sql])

  // Copy SQL to clipboard
  const handleCopy = () => {
    if (!query.display_sql) return
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(query.display_sql).then(() => {
        setCopied(true)
        setTimeout(() => setCopied(false), 1200)
      })
    } else {
      // Fallback for older browsers
      const textarea = document.createElement('textarea')
      textarea.value = query.display_sql
      document.body.appendChild(textarea)
      textarea.select()
      try {
        document.execCommand('copy')
        setCopied(true)
        setTimeout(() => setCopied(false), 1200)
      } catch (err) {
        alert('Copy not supported in this browser')
      }
      document.body.removeChild(textarea)
    }
  }

  // Render simplified backtrace
  const renderSimplified = () => (
    <div>
      {(showAll ? trace : trace.slice(0, 3)).map((frame, index) => (
        <div key={index} className="flex items-center text-sm bg-gray-800 p-3 rounded border border-gray-700 mb-1">
          <span className="font-medium text-gray-300">
            {frame.class ? `${frame.class}::${frame.function}()` : `${frame.function}()`}
          </span>
          <span className="text-gray-400 px-2"> in</span>
          <code className="text-xs text-gray-300 bg-gray-900 px-2 rounded">
            {getFileName(frame.file)}:{frame.line}
          </code>
        </div>
      ))}
      {!showAll && trace.length > 3 && (
        <button
          className="text-xs text-purple-400 mt-2 cursor-pointer"
          onClick={() => setShowAll(true)}
        >
          ... and {trace.length - 3} more frames
        </button>
      )}
      {showAll && trace.length > 3 && (
        <button
          className="text-xs text-purple-400 mt-2 cursor-pointer"
          onClick={() => setShowAll(false)}
        >
          Show less
        </button>
      )}
    </div>
  )

  return (
    <div className="border-t border-telescope-border bg-gray-50">
      <div className="p-6 space-y-6">
        {/* Query Details Section */}
        <div>
          <h3 className="text-lg font-semibold mb-4">Query Details</h3>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <div className="space-y-3">
                <div>
                  <div className="text-sm text-gray-500">Time</div>
                  <div className="font-medium">
                    {query.timestamp ? (
                      timeDisplayMode === 'relative'
                        ? formatDistanceToNow(new Date(query.timestamp), { addSuffix: true })
                        : format(new Date(query.timestamp), 'yyyy-MM-dd HH:mm:ss')
                    ) : ''}
                  </div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Hostname</div>
                  <div className="font-medium">{query.hostname}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Database</div>
                  <div className="font-medium">{database}</div>
                </div>
              </div>
            </div>
            <div>
              <div className="space-y-3">
                <div>
                  <div className="text-sm text-gray-500">Duration</div>
                  <div className="font-medium">{query.time}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-500">Request</div>
                  <div className="font-medium text-blue-600 hover:text-blue-800">
                    {query.request}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* SQL Query */}
        <div>
          <h3 className="text-lg font-semibold mb-4">Query</h3>
          <div className="relative bg-telescope-code-bg rounded-lg p-2 overflow-x-auto">
            {/* Copy button */}
            <button
              className="absolute top-2 right-2 text-xs text-gray-400 hover:text-purple-400 bg-gray-800 bg-opacity-70 rounded px-2 py-1 focus:outline-none transition"
              onClick={handleCopy}
              title="Copy SQL to clipboard"
              style={{ zIndex: 2 }}
            >
              {copied ? 'Copied!' : 'Copy'}
            </button>
            <pre className="font-mono text-telescope-code-text text-sm whitespace-pre-wrap">
              <code ref={sqlRef} className="language-sql">
                {query.display_sql}
              </code>
            </pre>
          </div>
          {/* Uncomment to show parameters: <QueryParameters parameters={query.parameters} /> */}
        </div>

        {/* Backtrace */}
        <div>
          <h3 className="text-lg font-semibold mb-4">Backtrace</h3>
          <div className="bg-telescope-code-bg rounded-lg p-4 overflow-x-auto">
            {trace.length > 0 ? renderSimplified() : (
              <div className="bg-telescope-code-bg rounded-lg p-4 text-sm text-gray-400">
                No backtrace available.
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

export default QueryDetails