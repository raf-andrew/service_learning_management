import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { web3ProviderService } from '../services/web3/Web3ProviderService'
import { web3Api } from '../services/web3/Web3Api'
import type { Web3Provider, Network, Contract, Transaction } from '../types/web3'

export const useWeb3Store = defineStore('web3', () => {
  // State
  const provider = ref<Web3Provider | null>(null)
  const networks = ref<Network[]>([])
  const currentContract = ref<Contract | null>(null)
  const transactions = ref<Transaction[]>([])
  const gasPriceEstimates = ref<any>(null)
  const error = ref<string | null>(null)

  // Getters
  const isConnected = computed(() => !!provider.value)
  const currentAddress = computed(() => provider.value?.address || null)
  const currentBalance = computed(() => provider.value?.balance || '0')
  const currentNetwork = computed(() => provider.value?.network || null)

  // Actions
  const initialize = async () => {
    try {
      if (!web3ProviderService.isMetaMaskInstalled()) {
        throw new Error('MetaMask is not installed')
      }

      const provider = await web3ProviderService.connect()
      if (!provider) {
        throw new Error('Connection failed')
      }

      await updateProviderState(provider)
      await fetchGasPrices()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Connection failed'
      throw err
    }
  }

  const connect = async () => {
    try {
      const provider = await web3ProviderService.connect()
      if (!provider) {
        throw new Error('Connection failed')
      }

      await updateProviderState(provider)
      await fetchGasPrices()
      return provider
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Connection failed'
      throw err
    }
  }

  const disconnect = async () => {
    try {
      await web3ProviderService.disconnect()
      reset()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Disconnection failed'
      throw err
    }
  }

  const switchNetwork = async (chainId: number) => {
    try {
      const provider = await web3ProviderService.switchNetwork(chainId)
      if (!provider) {
        throw new Error('Failed to switch network')
      }

      await updateProviderState(provider)
      await fetchGasPrices()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to switch network'
      throw err
    }
  }

  const loadContract = async (address: string, abi: any) => {
    try {
      const provider = web3ProviderService.getProvider()
      if (!provider) {
        throw new Error('Web3 provider not initialized')
      }

      const contract = { address, abi }
      currentContract.value = contract
      return contract
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load contract'
      throw err
    }
  }

  const callContractMethod = async (contractAddress: string, methodName: string, params: any[]) => {
    try {
      const signer = web3ProviderService.getSigner()
      if (!signer) {
        throw new Error('Web3 signer not initialized')
      }

      const contract = new web3ProviderService.Contract(contractAddress, currentContract.value?.abi, signer)
      const result = await (contract as any)[methodName](...params)
      return result
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Contract call failed'
      throw err
    }
  }

  const loadTransactions = async (page = 1) => {
    try {
      if (!provider.value?.address) {
        throw new Error('No address available')
      }

      const txs = await web3Api.getTransactionHistory(provider.value.address, page)
      if (page === 1) {
        transactions.value = txs
      } else {
        transactions.value = [...transactions.value, ...txs]
      }
      return txs
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load transactions'
      throw err
    }
  }

  const getTransactionDetails = async (hash: string) => {
    try {
      const tx = await web3Api.getTransactionDetails(hash)
      return tx
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to get transaction details'
      throw err
    }
  }

  const getNetworkStatus = async (chainId: number) => {
    try {
      const status = await web3Api.getNetworkStatus(chainId)
      return status
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to get network status'
      throw err
    }
  }

  const fetchGasPrices = async () => {
    try {
      const prices = await web3Api.getGasPriceEstimates()
      gasPriceEstimates.value = prices
      return prices
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch gas prices'
      throw err
    }
  }

  const clearError = () => {
    error.value = null
  }

  const reset = () => {
    provider.value = null
    networks.value = []
    currentContract.value = null
    transactions.value = []
    gasPriceEstimates.value = null
    error.value = null
  }

  // Helper functions
  const updateProviderState = async (newProvider: Web3Provider) => {
    provider.value = newProvider
    networks.value = await web3ProviderService.getNetworks()
  }

  return {
    // State
    provider,
    networks,
    currentContract,
    transactions,
    gasPriceEstimates,
    error,

    // Getters
    isConnected,
    currentAddress,
    currentBalance,
    currentNetwork,

    // Actions
    initialize,
    connect,
    disconnect,
    switchNetwork,
    loadContract,
    callContractMethod,
    loadTransactions,
    getTransactionDetails,
    getNetworkStatus,
    fetchGasPrices,
    clearError,
    reset
  }
}) 