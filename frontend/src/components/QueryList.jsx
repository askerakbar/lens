import  { useState, useEffect, useCallback } from 'react'
import { formatDistanceToNow, format } from 'date-fns'
import QueryDetails from './QueryDetails'

/**
 * QueryList displays a list of queries with expandable details.
 * @param {object} props
 * @param {Array} props.queries - Array of query objects.
 * @param {React.Ref} props.lastQueryElementRef - Ref for the last query element (for infinite scroll).
 */
function QueryList({ queries, lastQueryElementRef, timeDisplayMode = 'absolute' }) {
  const [expandedId, setExpandedId] = useState(null)
  // Memoize transformQuery to avoid unnecessary re-creation
  const transformQuery = useCallback((query) => {
    const connectionInfo = query.content?.connection || {}
    let connectionDisplay = 'default'
    if (connectionInfo.driver && connectionInfo.database) {
      connectionDisplay = `${connectionInfo.driver} : ${connectionInfo.database}`
    } else if (connectionInfo.driver) {
      connectionDisplay = connectionInfo.driver
    } else if (connectionInfo.database) {
      connectionDisplay = connectionInfo.database
    } else if (query.content?.batch_id) {
      connectionDisplay = query.content.batch_id.slice(0, 8)
    }
    return {
      id: query.id,
      query: query.content?.sql || '',
      statement: query.content?.sql || '',
      time: query.content?.duration ? `${(query.content.duration * 1000).toFixed(2)}ms` : 'N/A',
      connection: connectionDisplay,
      connection_raw: connectionInfo, // for details
      timestamp: query.content?.timestamp || query.created_at,
      hostname: query.content?.hostname || '',
      request: query.content?.request || '',
      parameters: query.content?.parameters,
      backtraces: query.content?.trace || [],
      error: query.error || false,
      slow: query.content?.duration > 0.1,
      display_sql: query.display_sql || '',
    }
  }, [])

  // No need for 'now' state, just use formatDistanceToNow directly

  useEffect(() => {
    // Collapse expanded details if queries change (optional, for UX)
    setExpandedId(null)
  }, [queries])

  return (
    <div className="space-y-4">
      {queries.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-48 text-center">
          <div className="text-2xl font-semibold text-gray-700 mb-2">No queries here yet!</div>
          <div className="text-gray-500 mb-4">Try refreshing a page in your app that runs some database queries, then check back here.</div>
          <div className="text-sm text-gray-400">This page will update automatically when new queries show up.</div>
        </div>
      ) : (
        queries.map((query, index) => {
          const transformedQuery = transformQuery(query)
          const isLast = index === queries.length - 1

          return (
            <div
              key={query.id}
              ref={isLast ? lastQueryElementRef : null}
              className="bg-telescope-card rounded-lg shadow-sm border border-telescope-border overflow-hidden"
            >
              <div
                className="p-4 cursor-pointer hover:bg-gray-50"
                onClick={() => setExpandedId(expandedId === query.id ? null : query.id)}
                role="button"
                tabIndex={0}
                aria-expanded={expandedId === query.id}
                onKeyPress={e => {
                  if (e.key === 'Enter' || e.key === ' ') {
                    setExpandedId(expandedId === query.id ? null : query.id)
                  }
                }}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <div
                      className={`w-2 h-2 rounded-full ${
                        transformedQuery.error
                          ? 'bg-telescope-error'
                          : transformedQuery.slow
                          ? 'bg-yellow-400'
                          : 'bg-telescope-success'
                      }`}
                    />
                    <div className="font-mono text-sm text-gray-600 truncate max-w-2xl">
                      {transformedQuery.display_sql}
                    </div>
                  </div>
                  <div className="flex items-center space-x-6">
                    <div className="text-sm text-gray-500">{transformedQuery.time}</div>
                    <div className="text-sm text-gray-500">
                      {timeDisplayMode === 'absolute'
                        ? format(new Date(transformedQuery.timestamp), 'MMM d, h:mm:ss a')
                        : formatDistanceToNow(new Date(transformedQuery.timestamp), { addSuffix: true })}
                    </div>
                  </div>
                </div>
              </div>
              {expandedId === query.id && <QueryDetails query={transformedQuery} timeDisplayMode={timeDisplayMode} />}
            </div>
          )
        })
      )}
    </div>
  )
}

export default QueryList