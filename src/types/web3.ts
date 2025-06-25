/**
 * @fileoverview Web3 Types - Type definitions for web3 functionality
 * @tags web3,types,interfaces
 */

export interface Web3Provider {
  address: string
  balance: string
  network: any
}

export interface Network {
  chainId: number
  name: string
  rpcUrl: string
}

export interface Contract {
  address: string
  abi: any
}

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