/**
 * @fileoverview Web3Api - Mock implementation for testing
 * @tags web3,api,service,mock
 */

export interface Transaction {
  hash: string
  from: string
  to: string
  value: string
  gas: string
  gasPrice: string
  nonce: number
  blockNumber: number
  timestamp: number
}

export interface NetworkStatus {
  chainId: number
  isOnline: boolean
  blockHeight: number
  gasPrice: string
}

export interface GasPriceEstimates {
  slow: string
  standard: string
  fast: string
}

export class Web3Api {
  private static instance: Web3Api

  static getInstance(): Web3Api {
    if (!Web3Api.instance) {
      Web3Api.instance = new Web3Api()
    }
    return Web3Api.instance
  }

  async getTransactionHistory(address: string, page: number = 1): Promise<Transaction[]> {
    // Mock transaction history
    const mockTransactions: Transaction[] = [
      {
        hash: '0x' + '1'.repeat(64),
        from: address,
        to: '0x' + '2'.repeat(40),
        value: '1000000000000000000',
        gas: '21000',
        gasPrice: '20000000000',
        nonce: 0,
        blockNumber: 12345678,
        timestamp: Date.now() - 3600000
      },
      {
        hash: '0x' + '3'.repeat(64),
        from: '0x' + '4'.repeat(40),
        to: address,
        value: '500000000000000000',
        gas: '21000',
        gasPrice: '20000000000',
        nonce: 1,
        blockNumber: 12345679,
        timestamp: Date.now() - 7200000
      }
    ]

    return mockTransactions
  }

  async getTransactionDetails(hash: string): Promise<Transaction | null> {
    // Mock transaction details
    return {
      hash,
      from: '0x' + '1'.repeat(40),
      to: '0x' + '2'.repeat(40),
      value: '1000000000000000000',
      gas: '21000',
      gasPrice: '20000000000',
      nonce: 0,
      blockNumber: 12345678,
      timestamp: Date.now() - 3600000
    }
  }

  async getNetworkStatus(chainId: number): Promise<NetworkStatus> {
    // Mock network status
    return {
      chainId,
      isOnline: true,
      blockHeight: 12345678,
      gasPrice: '20000000000'
    }
  }

  async getGasPriceEstimates(): Promise<GasPriceEstimates> {
    // Mock gas price estimates
    return {
      slow: '15000000000',
      standard: '20000000000',
      fast: '25000000000'
    }
  }
}

export const web3Api = Web3Api.getInstance() 