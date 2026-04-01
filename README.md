# Funeral Client Management System

## Overview

The Funeral Client Management System is designed to manage client records, services availed, and payment transactions in a funeral home. The system helps improve data organization, tracking, and reporting for better decision-making.

## Scope and Limitations

The system includes:

* Client record management
* Service tracking
* Payment recording
* Basic reporting
* Simple reminders (service dates and unpaid balances)

The system does NOT include:

* Staff management
* Inventory tracking
* Resource or facility management
* Advanced scheduling or conflict detection

## Users

* Staff – manages client records, cases, and payments within assigned branch
* Admin – manages users, services, and system data across branches
* Owner – views reports and financial summaries across all branches

## Key Features

* Centralized client and case records
* Service package selection and tracking
* Payment recording with balance monitoring
* Dashboard with “Needs Attention” and schedule overview
* Basic notifications and reminders

## Technologies Used

* Laravel (PHP Framework)
* MySQL Database
* HTML, CSS, JavaScript
* Tailwind CSS

## Installation Guide

1. Clone the repository:

   ```
   git clone https://github.com/Bayangatjohn1/funeral-client-management-system.git
   ```

2. Install dependencies:

   ```
   composer install
   npm install
   ```

3. Setup environment:

   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure database in `.env`

5. Run migrations:

   ```
   php artisan migrate
   ```

6. Start the server:

   ```
   php artisan serve
   ```

## Purpose

This project is developed as part of the capstone requirement for System Integration and Architecture (SIA).
