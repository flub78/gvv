# PRD: Aircraft Booking System

## 1. Introduction

This document outlines the requirements for a new aircraft booking system within the GVV application. The system will enable club members to reserve aircraft, manage their bookings, and check instructor availability. It is designed to prevent scheduling conflicts and provide a clear, centralized view of resource allocation.

## 2. Goals

*   **Streamline Reservations:** Simplify the process for members to book and manage aircraft reservations.
*   **Prevent Conflicts:** Eliminate double-bookings for both aircraft and instructors.
*   **Improve Visibility:** Provide clear and up-to-date schedules for all aircraft and instructors.
*   **Flexible Permissions:** Grant appropriate access levels for standard pilots, instructors, and administrators. Use the standard GVV authorization mechanism described in...
*   **Manage Fleet Availability:** Allow authorized personnel to block aircraft for maintenance or other reasons.

## 3. User Roles & Personas

*   **Pilot:** A standard club member who can book flights for themselves.
*   **Student Pilot:** A pilot in training who needs to book flights with an instructor.
*   **Instructor:** A certified instructor who can teach students and also manage the broader flight schedule.
*   **Administrator/Mechanic:** A user responsible for the operational status of the aircraft fleet.

## 4. User Stories

| As a... | I want to... | So that I can... |
| :--- | :--- | :--- |
| Pilot | See a schedule of all available aircraft | Find and book a plane for a personal flight. |
| Pilot | Modify the date or time of my reservation | Adjust my plans without needing to cancel and re-book. |
| Pilot | Cancel my reservation | Free up the aircraft for other members if I can no longer fly. |
| Student Pilot | View the availability of all instructors | Schedule a training session when an instructor is free. |
| Student Pilot | Book an aircraft and an instructor together | Ensure I have both the plane and the instructor for my lesson. |
| Instructor | View and manage all reservations in the system | Assist members, resolve conflicts, and manage the daily schedule. |
| Administrator | Mark an aircraft as "unavailable" for a period | Prevent bookings during maintenance or inspections. |
| User | Be prevented from booking a reserved aircraft | Avoid showing up for a flight that someone else has booked. |
| User | Be prevented from booking a busy instructor | Ensure the instructor is actually available to fly with me. |
| User | Be prevented from booking if the sum of my reservations exceed my credit limit | | 
| User | interact with the reservation system by clicking on the calendar or existing reservations| easily update the reservations|
| User | drag and drop the reservations in the calendar| easily update the reservations|
| User | extend the reservations by dragging the end| easily update the reservations|

## 5. Functional Requirements

### 5.1. Core Reservation Management

*   **Create Reservation:** Authenticated users can book an aircraft for a specific date and time slot.
*   **Modify Reservation:** A user can change the date, time, or aircraft of their **own** reservation.
*   **Delete Reservation:** A user can cancel their **own** reservation.
*   **View Reservations:** Users can see their own upcoming reservations. A calendar view (daily/weekly/monthly) should be available to see all aircraft bookings.

### 5.2. Permissions and Roles

*   **Pilots** can only create, modify, or delete their own reservations.
*   **Instructors** have elevated privileges. They can create, modify, and delete **any** member's reservation.
*   An **Administrator** role (or similar) is required to manage aircraft availability.

### 5.3. Aircraft Availability

*   Administrators must have a function to create an "unavailability block" for an aircraft.
*   This block requires a start date/time, an end date/time, and a mandatory reason (e.g., "50-hour inspection," "Private event").
*   The system **must prohibit** creating or moving a reservation into a time slot that overlaps with an unavailability block.

### 5.4. Instructor Availability

*   The system must provide a dedicated view (e.g., a consolidated calendar) showing when instructors are booked.
*   An instructor is considered unavailable if they are already assigned to another reservation in that time slot.

### 5.5. Conflict Prevention (Business Rules)

*   **Aircraft Conflict:** The system must reject any attempt to create or move a reservation to a time that overlaps with an existing booking for the **same aircraft**.
*   **Instructor Conflict:** The system must reject any attempt to book a flight with an instructor who is already scheduled for an overlapping flight.
*   Reservations cannot be created or moved into the past.

## 6. Non-Functional Requirements

*   **Usability:** The interface should be intuitive, with a strong preference for a visual, drag-and-drop calendar for making and modifying bookings.
*   **Security:** All booking modifications must be authenticated and authorized based on user roles.
*   **Performance:** The schedule view must load quickly, even with a large number of bookings.
*   **Code reuse:** The calendar controller has already a mechanism for member to say their intention. Reuse the same calendar javascript library. This calendar use a Google calendar as data source, for airplane reservation the data source can be the local database.

## 7. Out of Scope

*   Automated billing or payment processing for flights.
*   A waitlist feature for fully booked aircraft or instructors.
*   Automated notifications (email, SMS) for booking confirmations, reminders, or cancellations.
*   Advanced recurring reservation features (e.g., "book every Tuesday for 6 weeks").
*   

## 8. Mockups and ideas

* mockups/gvv_presence_calendar.png - existing calendar in GVV
* mockups/gvv_presence_popup.png - existing presence popup in GVV
* mockups/openflyers_booking_calendar.png - an example of booking calendar from another application
* mockups/openflyers_booking_form.png - an example of booking form from another application


