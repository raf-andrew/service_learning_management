terraform {
  backend "s3" {
    bucket         = "terraform-state-${var.environment}"
    key            = "terraform.tfstate"
    region         = var.region
    dynamodb_table = "terraform-lock-${var.environment}"
  }
}
