/**
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 */



import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import listPlugin from "@fullcalendar/list";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

export function calendarInit() {
  const calendarWrapper = document.querySelector("#calendar");

  if (calendarWrapper) {
    // Calendar Date variable
    const newDate = new Date();
    const getDynamicMonth = () => {
      const month = newDate.getMonth() + 1;
      return month < 10 ? `0${month}` : `${month}`;
    };

    // Calendar Modal Elements
    const getModalTitleEl = document.querySelector("#event-title");
    const getModalStartDateEl = document.querySelector("#event-start-date");
    const getModalEndDateEl = document.querySelector("#event-end-date");
    const getModalAddBtnEl = document.querySelector(".btn-add-event");
    const getModalUpdateBtnEl = document.querySelector(".btn-update-event");
    const getModalHeaderEl = document.querySelector("#eventModalLabel");

    const calendarsEvents = {
      Danger: "danger",
      Success: "success",
      Primary: "primary",
      Warning: "warning",
    };

    // Calendar Elements and options
    const calendarEl = document.querySelector("#calendar");

    const calendarHeaderToolbar = {
      left: "prev,next addEventButton",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    };

    const calendarEventsList = [
      {
        id: "1",
        title: "Event Conf.",
        start: `${newDate.getFullYear()}-${getDynamicMonth()}-01`,
        extendedProps: { calendar: "Danger" },
      },
      {
        id: "2",
        title: "Seminar #4",
        start: `${newDate.getFullYear()}-${getDynamicMonth()}-07`,
        end: `${newDate.getFullYear()}-${getDynamicMonth()}-10`,
        extendedProps: { calendar: "Success" },
      },
      {
        id: "3",
        title: "Meeting #5",
        start: `${newDate.getFullYear()}-${getDynamicMonth()}-09T16:00:00`,
        extendedProps: { calendar: "Primary" },
      },
      {
        id: "4",
        title: "Submission #1",
        start: `${newDate.getFullYear()}-${getDynamicMonth()}-16T16:00:00`,
        extendedProps: { calendar: "Warning" },
      },
      {
        id: "5",
        title: "Seminar #6",
        start: `${newDate.getFullYear()}-${getDynamicMonth()}-11`,
        end: `${newDate.getFullYear()}-${getDynamicMonth()}-13`,
        extendedProps: { calendar: "Danger" },
      },
    ];

    // Modal Functions
    const openModal = () => {
      const modal = document.getElementById("eventModal");
      if (modal) {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden"; // Prevent background scroll
      }
    };

    const closeModal = () => {
      const modal = document.getElementById("eventModal");
      if (modal) {
        modal.style.display = "none";
        document.body.style.overflow = ""; // Restore scroll
      }
      resetModalFields();
    };

    // Reset modal fields
    function resetModalFields() {
      if (getModalTitleEl) getModalTitleEl.value = "";
      if (getModalStartDateEl) getModalStartDateEl.value = "";
      if (getModalEndDateEl) getModalEndDateEl.value = "";
      
      const getModalIfCheckedRadioBtnEl = document.querySelector(
        'input[name="event-level"]:checked'
      );
      if (getModalIfCheckedRadioBtnEl) {
        getModalIfCheckedRadioBtnEl.checked = false;
      }
    }

    // Calendar Select function (when user clicks/drags on calendar)
    const calendarSelect = (info) => {
      resetModalFields();

      // Update modal header
      if (getModalHeaderEl) {
        getModalHeaderEl.textContent = "Add Event";
      }

      // Show Add button, hide Update button
      if (getModalAddBtnEl) getModalAddBtnEl.style.display = "flex";
      if (getModalUpdateBtnEl) getModalUpdateBtnEl.style.display = "none";

      // Set dates from selection
      if (getModalStartDateEl) getModalStartDateEl.value = info.startStr;
      if (getModalEndDateEl) {
        getModalEndDateEl.value = info.endStr || info.startStr;
      }

      openModal();
    };

    // Calendar AddEvent button click
    const calendarAddEvent = () => {
      resetModalFields();

      // Update modal header
      if (getModalHeaderEl) {
        getModalHeaderEl.textContent = "Add Event";
      }

      // Show Add button, hide Update button
      if (getModalAddBtnEl) getModalAddBtnEl.style.display = "flex";
      if (getModalUpdateBtnEl) getModalUpdateBtnEl.style.display = "none";

      // Set default start date to today
      const currentDate = new Date();
      const yyyy = currentDate.getFullYear();
      const mm = String(currentDate.getMonth() + 1).padStart(2, "0");
      const dd = String(currentDate.getDate()).padStart(2, "0");
      const combineDate = `${yyyy}-${mm}-${dd}`;

      if (getModalStartDateEl) getModalStartDateEl.value = combineDate;

      openModal();
    };

    // Calendar Event Click function (when user clicks existing event)
    const calendarEventClick = (info) => {
      const eventObj = info.event;

      if (eventObj.url) {
        window.open(eventObj.url);
        info.jsEvent.preventDefault();
      } else {
        resetModalFields();

        // Update modal header
        if (getModalHeaderEl) {
          getModalHeaderEl.textContent = "Edit Event";
        }

        // Get event details
        const getModalEventId = eventObj.id;
        const getModalEventLevel = eventObj.extendedProps.calendar;

        // Set form values
        if (getModalTitleEl) getModalTitleEl.value = eventObj.title;
        if (getModalStartDateEl) {
          getModalStartDateEl.value = eventObj.startStr.split("T")[0];
        }
        if (getModalEndDateEl) {
          getModalEndDateEl.value = eventObj.endStr
            ? eventObj.endStr.split("T")[0]
            : "";
        }

        // Check the correct radio button
        const getModalCheckedRadioBtnEl = document.querySelector(
          `input[value="${getModalEventLevel}"]`
        );
        if (getModalCheckedRadioBtnEl) {
          getModalCheckedRadioBtnEl.checked = true;
        }

        // Store event ID for update
        if (getModalUpdateBtnEl) {
          getModalUpdateBtnEl.dataset.fcEventPublicId = getModalEventId;
        }

        // Hide Add button, show Update button
        if (getModalAddBtnEl) getModalAddBtnEl.style.display = "none";
        if (getModalUpdateBtnEl) getModalUpdateBtnEl.style.display = "flex";

        openModal();
      }
    };

    // Initialize Calendar
    const calendar = new Calendar(calendarEl, {
      plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
      selectable: true,
      initialView: "dayGridMonth",
      initialDate: `${newDate.getFullYear()}-${getDynamicMonth()}-07`,
      headerToolbar: calendarHeaderToolbar,
      events: calendarEventsList,
      select: calendarSelect,
      eventClick: calendarEventClick,
      displayEventTime: false, // Hide time display
      customButtons: {
        addEventButton: {
          text: "Add Event +",
          click: calendarAddEvent,
        },
      },
      // eventClassNames({ event: calendarEvent }) {
      //   const getColorValue =
      //     calendarsEvents[calendarEvent._def.extendedProps.calendar];
      //   return [`event-fc-color`, `fc-bg-${getColorValue}`];
      // },
      // Optional: Custom event content without time
      eventContent(eventInfo) {
        const colorClass = `fc-bg-${eventInfo.event.extendedProps.calendar.toLowerCase()}`
        return {
          html: `
            <div class="event-fc-color flex fc-event-main ${colorClass} p-1 rounded-sm">
              <div class="fc-daygrid-event-dot"></div>
              <div class="fc-event-time">${eventInfo.timeText}</div>
              <div class="fc-event-title">${eventInfo.event.title}</div>
            </div>
          `,
        }
      },
    });

    // Update Calendar Event
    // if (getModalUpdateBtnEl) {
    //   getModalUpdateBtnEl.addEventListener("click", () => {
    //     const getPublicID = getModalUpdateBtnEl.dataset.fcEventPublicId;
    //     const getTitleUpdatedValue = getModalTitleEl.value;
    //     const setModalStartDateValue = getModalStartDateEl.value;
    //     const setModalEndDateValue = getModalEndDateEl.value;
    //     const getEvent = calendar.getEventById(getPublicID);
    //     const getModalUpdatedCheckedRadioBtnEl = document.querySelector(
    //       'input[name="event-level"]:checked'
    //     );

    //     const getModalUpdatedCheckedRadioBtnValue =
    //       getModalUpdatedCheckedRadioBtnEl
    //         ? getModalUpdatedCheckedRadioBtnEl.value
    //         : "";

    //     if (getEvent) {
    //       getEvent.setProp("title", getTitleUpdatedValue);
    //       getEvent.setDates(setModalStartDateValue, setModalEndDateValue);
    //       getEvent.setExtendedProp("calendar", getModalUpdatedCheckedRadioBtnValue);
    //     }
        
    //     closeModal();
    //   });
    // }
    if (getModalUpdateBtnEl) {
      getModalUpdateBtnEl.addEventListener("click", () => {
        const getPublicID = getModalUpdateBtnEl.dataset.fcEventPublicId;
        const getTitleUpdatedValue = getModalTitleEl.value;
        const setModalStartDateValue = getModalStartDateEl.value;
        const setModalEndDateValue = getModalEndDateEl.value;
        const getEvent = calendar.getEventById(getPublicID);
        const getModalUpdatedCheckedRadioBtnEl = document.querySelector(
          'input[name="event-level"]:checked'
        );

        const getModalUpdatedCheckedRadioBtnValue =
          getModalUpdatedCheckedRadioBtnEl
            ? getModalUpdatedCheckedRadioBtnEl.value
            : "";

        if (getEvent) {
          // Remove the old event
          getEvent.remove();
          
          // Add updated event with all properties
          calendar.addEvent({
            id: getPublicID,
            title: getTitleUpdatedValue,
            start: setModalStartDateValue,
            end: setModalEndDateValue,
            allDay: true,
            extendedProps: { calendar: getModalUpdatedCheckedRadioBtnValue },
          });
        }
        
        closeModal();
      });
    }

    // Add Calendar Event
    if (getModalAddBtnEl) {
      getModalAddBtnEl.addEventListener("click", () => {
        const getModalCheckedRadioBtnEl = document.querySelector(
          'input[name="event-level"]:checked'
        );

        const getTitleValue = getModalTitleEl.value;
        const setModalStartDateValue = getModalStartDateEl.value;
        const setModalEndDateValue = getModalEndDateEl.value;
        const getModalCheckedRadioBtnValue = getModalCheckedRadioBtnEl
          ? getModalCheckedRadioBtnEl.value
          : "";

        calendar.addEvent({
          id: Date.now().toString(),
          title: getTitleValue,
          start: setModalStartDateValue,
          end: setModalEndDateValue,
          allDay: true,
          extendedProps: { calendar: getModalCheckedRadioBtnValue },
        });
        
        closeModal();
      });
    }

    // Render Calendar
    calendar.render();

    // Close modal event listeners
    document.querySelectorAll(".modal-close-btn").forEach((btn) => {
      btn.addEventListener("click", closeModal);
    });

    // Close when clicking outside modal
    window.addEventListener("click", (event) => {
      const modal = document.getElementById("eventModal");
      if (event.target === modal) {
        closeModal();
      }
    });
  }
}

export default calendarInit;
