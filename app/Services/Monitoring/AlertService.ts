export type AlertType = 'info' | 'warning' | 'error' | 'success';

export interface AlertOptions {
    title: string;
    message: string;
    type: AlertType;
    metadata?: Record<string, any>;
}

export interface AlertService {
    sendAlert(title: string, message: string, type: AlertType, metadata?: Record<string, any>): Promise<void>;
    sendAlertWithOptions(options: AlertOptions): Promise<void>;
} 