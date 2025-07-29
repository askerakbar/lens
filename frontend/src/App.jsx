import  { useState, useEffect, useCallback, useRef } from 'react'
import QueryList from './components/QueryList'
import { queryService } from './services/queryService'
import { ArrowPathIcon, TrashIcon } from '@heroicons/react/24/solid'
import SearchInput from './components/SearchInput'
import { Cog6ToothIcon } from '@heroicons/react/24/outline'

/**
 * App is the main component for the query monitor UI.
 * Handles fetching, filtering, and displaying query logs.
 */
function App() {
  // State
  const [searchQuery, setSearchQuery] = useState('')
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState('')
  const [filter, setFilter] = useState('all')
  const [queries, setQueries] = useState([])
  const [loading, setLoading] = useState(true)
  const [loadingMore, setLoadingMore] = useState(false)
  const [error, setError] = useState(null)
  const [currentPage, setCurrentPage] = useState(1)
  const [hasMore, setHasMore] = useState(true)
  const [clearing, setClearing] = useState(false)
  const [hasNewEntries, setHasNewEntries] = useState(false)
  const [autoLoadsNewEntries, setAutoLoadsNewEntries] = useState(() => {
    const stored = localStorage.getItem('autoLoadsNewEntries')
    return stored === null ? true : stored === 'true'
  })
  // Time display mode state
  const [timeDisplayMode, setTimeDisplayMode] = useState(() => {
    return localStorage.getItem('timeDisplayMode') || 'relative'
  })
  const [showConfig, setShowConfig] = useState(false)

  // Refs
  const observer = useRef()
  const newEntriesTimeout = useRef()
  const newEntriesTimer = 2500
  const searchInputRef = useRef(null)

  // Reset pagination when filter or search changes
  const resetPagination = useCallback(() => {
    setQueries([])
    setCurrentPage(1)
    setHasMore(true)
    setError(null)
    setHasNewEntries(false)
  }, [])

  // Fetch queries from API or mock
  const fetchQueries = useCallback(async (page = 1, isLoadMore = false) => {
    try {
      if (!isLoadMore) setLoading(true)
      else setLoadingMore(true)
      const params = {
        page,
        perPage: 20,
        filter: filter !== 'all' ? filter : undefined,
        search: debouncedSearchQuery.trim() || undefined
      }
      const data = await queryService.getQueries(params)
      if (isLoadMore) setQueries(prev => [...prev, ...data.queries])
      else setQueries(data.queries)
      const totalPages = Math.ceil(data.total / data.perPage)
      setHasMore(page < totalPages)
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
      setLoadingMore(false)
    }
  }, [filter, debouncedSearchQuery])

  // Check for new entries
  const checkForNewEntries = useCallback(() => {
    if (newEntriesTimeout.current) clearTimeout(newEntriesTimeout.current)
    newEntriesTimeout.current = setTimeout(async () => {
      try {
        const params = {
          page: 1,
          perPage: 1,
          filter: filter !== 'all' ? filter : undefined,
          search: debouncedSearchQuery.trim() || undefined
        }
        const response = await queryService.getQueries(params)
        if (response.queries.length && !queries.length) {
          loadNewEntries()
        } else if (response.queries.length && queries.length) {
          const latestEntry = response.queries[0]
          const currentLatest = queries[0]
          if (latestEntry.id !== currentLatest?.id) {
            if (autoLoadsNewEntries) loadNewEntries()
            else setHasNewEntries(true)
          } else {
            checkForNewEntries()
          }
        } else {
          checkForNewEntries()
        }
      } catch (err) {
        // eslint-disable-next-line no-console
        console.error('Error checking for new entries:', err)
        checkForNewEntries()
      }
    }, newEntriesTimer)
  }, [filter, debouncedSearchQuery, queries, autoLoadsNewEntries])

  // Load new entries
  const loadNewEntries = useCallback(async () => {
    try {
      const params = {
        page: 1,
        perPage: 20,
        filter: filter !== 'all' ? filter : undefined,
        search: debouncedSearchQuery.trim() || undefined
      }
      const data = await queryService.getQueries(params)
      setQueries(data.queries)
      setCurrentPage(1)
      setHasMore(data.queries.length === data.perPage)
      setHasNewEntries(false)
      checkForNewEntries()
    } catch (err) {
      setError(err.message)
    }
  }, [filter, debouncedSearchQuery, checkForNewEntries])

  // Persist auto-load setting
  useEffect(() => {
    localStorage.setItem('autoLoadsNewEntries', autoLoadsNewEntries)
  }, [autoLoadsNewEntries])

  // Persist time display mode
  useEffect(() => {
    localStorage.setItem('timeDisplayMode', timeDisplayMode)
  }, [timeDisplayMode])

  // Initial load and when filter/search changes
  useEffect(() => {
    if (newEntriesTimeout.current) clearTimeout(newEntriesTimeout.current)
    resetPagination()
    fetchQueries(1, false)
  }, [filter, debouncedSearchQuery, fetchQueries, resetPagination])

  // Start checking for new entries after initial load
  useEffect(() => {
    if (!loading && autoLoadsNewEntries) {
      checkForNewEntries()
    } else {
      if (newEntriesTimeout.current) clearTimeout(newEntriesTimeout.current)
    }
    return () => {
      if (newEntriesTimeout.current) clearTimeout(newEntriesTimeout.current)
    }
  }, [loading, checkForNewEntries, autoLoadsNewEntries])

  // Load more data when page changes
  useEffect(() => {
    if (currentPage > 1) fetchQueries(currentPage, true)
  }, [currentPage, fetchQueries])

  // Intersection Observer callback for infinite scroll
  const lastQueryElementRef = useCallback(node => {
    if (loading || loadingMore) return
    if (observer.current) observer.current.disconnect()
    observer.current = new window.IntersectionObserver(entries => {
      if (entries[0].isIntersecting && hasMore) {
        setCurrentPage(prevPage => prevPage + 1)
      }
    }, {
      threshold: 0.1,
      rootMargin: '100px'
    })
    if (node) observer.current.observe(node)
  }, [loading, loadingMore, hasMore])

  // Clean up timeout on unmount
  useEffect(() => {
    return () => {
      if (newEntriesTimeout.current) clearTimeout(newEntriesTimeout.current)
    }
  }, [])

  // Clear queries handler
  const handleClearQueries = async () => {
    setClearing(true)
    try {
      await queryService.clearQueries()
      await fetchQueries(1, false)
    } catch (err) {
      setError('Failed to clear queries')
    } finally {
      setClearing(false)
    }
  }

  if (loading && queries.length === 0) {
    return (
      <div className="min-h-screen bg-telescope-bg flex items-center justify-center">
        <div className="text-telescope-text">Loading queries...</div>
      </div>
    )
  }

  if (error && queries.length === 0) {
    let userMessage = 'An error occurred while loading queries.'
    if (
      error.includes('Database unavailable') ||
      error.includes('logs table missing') ||
      error.includes('Failed to fetch queries')
    ) {
      userMessage = 'Unable to load query logs. The database may be unavailable or the logs table is missing.'
    }
    return (
      <div className="min-h-screen bg-telescope-bg flex items-center justify-center">
        <div className="text-red-500">{userMessage}</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-telescope-bg">
      <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center mb-6">
          <div className='flex space-x-4 invisible'> {/* Disable filters for now */}
            <button
              onClick={() => setFilter('all')}
              className={`px-4 py-2 rounded-md ${
                filter === 'all'
                  ? 'bg-telescope-card shadow-sm text-telescope-text'
                  : 'text-gray-600 hover:text-telescope-text'
              }`}
            >
              All
            </button>
            <button
              onClick={() => setFilter('slow')}
              className={`px-4 py-2 rounded-md ${
                filter === 'slow'
                  ? 'bg-telescope-card shadow-sm text-telescope-text'
                  : 'text-gray-600 hover:text-telescope-text'
              }`}
            >
              Slow
            </button>
            <button
              onClick={() => setFilter('failed')}
              className={`px-4 py-2 rounded-md ${
                filter === 'failed'
                  ? 'bg-telescope-card shadow-sm text-telescope-text'
                  : 'text-gray-600 hover:text-telescope-text'
              }`}
            >
              Failed
            </button>
          </div>
          <div className="flex items-center space-x-4">
            <div className="flex items-center space-x-2">
              <button
                onClick={() => setAutoLoadsNewEntries(v => !v)}
                className={`p-2 transition-colors
                  ${autoLoadsNewEntries
                    ? 'bg-green-500 text-white border-green-600 shadow'
                    : 'bg-gray-200 text-gray-500 border-gray-300 hover:bg-gray-300'}
                `}
                title={autoLoadsNewEntries ? 'Auto-loading enabled' : 'Auto-loading disabled'}
              >
                <ArrowPathIcon className="h-4 w-4" />
              </button>
              <button
                onClick={handleClearQueries}
                className="ml-2 clear-queries-btn"
                disabled={clearing}
                title="Clear all logged queries"
              >
                <TrashIcon className="h-5 w-5" />
              </button>
            </div>
            <SearchInput
              ref={searchInputRef}
              placeholder="Search..."
              value={searchQuery}
              onChange={e => setSearchQuery(e.target.value)}
              onDebouncedChange={setDebouncedSearchQuery}
            />
          </div>
        </div>
        {/* New entries notification */}
        {hasNewEntries && !autoLoadsNewEntries && (
          <div className="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-pulse mr-3"></div>
                <span className="text-blue-800">New queries are available</span>
              </div>
              <button
                onClick={loadNewEntries}
                className="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600"
              >
                Load New Entries
              </button>
            </div>
          </div>
        )}
        <QueryList 
          queries={queries} 
          lastQueryElementRef={lastQueryElementRef}
          timeDisplayMode={timeDisplayMode}
        />
        {loadingMore && (
          <div className="flex justify-center py-8">
            <div className="text-telescope-text">Loading more queries...</div>
          </div>
        )}
        {!hasMore && queries.length > 0 && (
          <div className="flex justify-center py-8">
            <div className="text-gray-500">No more queries to load</div>
          </div>
        )}
        {error && queries.length > 0 && (
          <div className="flex justify-center py-4">
            <div className="text-red-500 text-sm">Error loading more queries: {error}</div>
          </div>
        )}
      </div>
      {/* Settings Icon and Modal */}
      <button
        className="fixed bottom-6 right-6 z-50 bg-white border border-gray-200 rounded-full shadow-lg p-2 hover:bg-gray-100 transition-colors"
        onClick={() => setShowConfig(true)}
        title="Configure time display"
        aria-label="Configure time display"
      >
        <Cog6ToothIcon className="h-6 w-6 text-gray-500" />
      </button>
      {showConfig && (
        <div className="fixed inset-0 z-50 flex items-end justify-end">
          <div className="absolute inset-0 bg-black bg-opacity-10" onClick={() => setShowConfig(false)} />
          <div className="relative m-6 bg-white rounded-lg shadow-lg p-6 w-72 border border-gray-200">
            <h3 className="text-lg font-semibold mb-4">Time Display</h3>
            <div className="space-y-3">
              <label className="flex items-center space-x-2 cursor-pointer">
                <input
                  type="radio"
                  name="timeDisplayMode"
                  value="relative"
                  checked={timeDisplayMode === 'relative'}
                  onChange={() => setTimeDisplayMode('relative')}
                />
                <span>Relative (e.g. 2 minutes ago)</span>
              </label>
              <label className="flex items-center space-x-2 cursor-pointer">
                <input
                  type="radio"
                  name="timeDisplayMode"
                  value="absolute"
                  checked={timeDisplayMode === 'absolute'}
                  onChange={() => setTimeDisplayMode('absolute')}
                />
                <span>Absolute (e.g. 2024-06-01 12:34:56)</span>
              </label>
            </div>
            <button
              className="mt-6 w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition-colors"
              onClick={() => setShowConfig(false)}
            >
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

export default App