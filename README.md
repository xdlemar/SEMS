## 📌 Overview

The **Smart Employee Management System (SEMS)** is a web-based application designed to manage employee information and streamline attendance tracking using RFID technology.

The system uses physical RFID cards and an RFID scanner to identify employees. A Python script reads the RFID card data, allowing the system to use the scanned card identifier for employee attendance recording.

SEMS aims to reduce manual attendance logging and provide a more convenient and organized way to manage employee attendance records.

## ✨ Key Features

- **RFID-Based Identification** — Uses physical RFID cards to identify employees.
- **Python RFID Reader Integration** — Uses Python to read RFID card data through an RFID scanner.
- **Employee Attendance Tracking** — Supports RFID-based employee time-in recording.
- **Employee Information Management** — Organizes employee information.
- **Digital Attendance Records** — Supports storing attendance information for management and monitoring.

## 🛠️ Technology Stack

- **PHP** — Web application and backend functionality.
- **Python** — RFID card reading and scanner integration.
- **MySQL** — Database scripts and data management.
- **Composer** — PHP dependency management.
- **HTML, CSS, and JavaScript** — Web interface, if used by the application.

## 🔄 How the System Works

1. Each employee is assigned an RFID card.
2. The employee taps their card on the RFID scanner.
3. Python reads the card's identifier.
4. The system uses the identifier to associate the scan with the registered employee.
5. The attendance record is processed and stored according to the system's implementation.
