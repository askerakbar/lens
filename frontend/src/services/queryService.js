/**
 * QueryService provides methods to fetch and clear query logs from the API or mock data.
 * Handles both real API and mock data for development/testing.
 */
import mockData from '../data/mockData'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/lens/api'
const USE_MOCK_DATA = import.meta.env.VITE_USE_MOCK_DATA === 'true'

class QueryService {
  constructor() {
    this.baseUrl = API_BASE_URL
  }

  /**
   * Fetch queries from the API or mock data.
   * @param {object} params
   * @returns {Promise<object>} Query data
   */
  async getQueries(params = {}) {
    try {
      const queryParams = new URLSearchParams()
      if (params.page) queryParams.append('page', params.page)
      if (params.perPage) queryParams.append('perPage', params.perPage)
      if (params.filter) queryParams.append('filter', params.filter)
      if (params.search) queryParams.append('search', params.search)
      const url = `${this.baseUrl}/queries?${queryParams.toString()}`

      // Use mock data if in development or explicitly enabled
      if (import.meta.env.DEV || USE_MOCK_DATA) {
        return this.getMockData(params)
      }

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      })
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      const data = await response.json()
      return {
        queries: data.queries || [],
        page: data.page || params.page || 1,
        perPage: data.perPage || params.perPage || 20,
        total: data.total || data.queries?.length || 0,
        totalPages: data.totalPages || Math.ceil((data.total || data.queries?.length || 0) / (data.perPage || params.perPage || 20))
      }
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Error fetching queries:', error)
      throw new Error('Failed to fetch queries')
    }
  }

  /**
   * Return mock query data for development/testing.
   * @param {object} params
   * @returns {Promise<object>} Mock query data
   */
  getMockData(params = {}) {
    return new Promise((resolve) => {
      setTimeout(() => {
        const page = params.page || 1
        const perPage = params.perPage || 20
        const filter = params.filter
        const search = params.search?.toLowerCase()
        let filteredQueries = [...mockData.queries]
        if (search) {
          filteredQueries = filteredQueries.filter(query => 
            query.content?.sql?.toLowerCase().includes(search)
          )
        }
        if (filter === 'slow') {
          filteredQueries = filteredQueries.filter(query => 
            query.content?.duration > 0.1
          )
        } else if (filter === 'failed') {
          filteredQueries = filteredQueries.filter(query => 
            query.error === true
          )
        }
        const startIndex = (page - 1) * perPage
        const endIndex = startIndex + perPage
        const paginatedQueries = filteredQueries.slice(startIndex, endIndex)
        if (paginatedQueries.length < perPage && page > 1) {
          const additionalQueries = mockData.queries.map((query, index) => ({
            ...query,
            id: query.id + (page * 1000) + index,
            content: {
              ...query.content,
              timestamp: new Date(Date.now() - Math.random() * 86400000 * 30).toISOString()
            }
          })).slice(0, perPage - paginatedQueries.length)
          paginatedQueries.push(...additionalQueries)
        }
        resolve({
          queries: paginatedQueries,
          page: page,
          perPage: perPage,
          total: filteredQueries.length + (page < 5 ? 100 : 0),
          totalPages: Math.ceil((filteredQueries.length + (page < 5 ? 100 : 0)) / perPage)
        })
      }, 300)
    })
  }

  /**
   * Clear all queries (API or mock).
   * @returns {Promise<object>} Result of clear operation
   */
  async clearQueries() {
    if (import.meta.env.DEV || USE_MOCK_DATA) {
      mockData.queries = []
      return { success: true, message: 'Mock queries cleared', cleared: 0 }
    }
    const url = `${this.baseUrl}/queries/clear`
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    })
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    return response.json()
  }
}

export const queryService = new QueryService()