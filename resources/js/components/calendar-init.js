import { Calendar } from "fullcalendar";
import dayGridPlugin from "fullcalendar/daygrid";
import interactionPlugin from "fullcalendar/interaction";
import esLocale from "fullcalendar/locales/es";
import themePlugin from "fullcalendar/themes/classic";

import "fullcalendar/skeleton.css";
import "fullcalendar/themes/classic/palette.css";
import "fullcalendar/themes/classic/theme.css";

// Read-only calendar: users only see the marked days (e.g. working days),
// they cannot create or edit events.
export function calendarInit() {
  const calendarEl = document.querySelector("#calendar");

  if (!calendarEl) return;

  const isRtl = document.documentElement.dir === "rtl";
  let isMobile = window.innerWidth < 640;

  // Background colors for marked days, by event "type"
  const BG_EVENT_CLASSES = {
    success:
      "bg-success-100! opacity-100! dark:bg-success-500/20! text-success-700! dark:text-success-400!",
    primary:
      "bg-brand-100! opacity-100! dark:bg-brand-500/20! text-brand-700! dark:text-brand-400!",
    danger:
      "bg-error-100! opacity-100! dark:bg-error-500/20! text-error-700! dark:text-error-400!",
    warning:
      "bg-warning-100! opacity-100! dark:bg-warning-500/20! text-warning-700! dark:text-warning-400!",
    weekend:
      "bg-gray-100! opacity-100! dark:bg-white/5! text-gray-500! dark:text-gray-400!",
  };

  const EVENT_COLOR_CLASSES = {
    success: {
      bg: "border border-success-100 bg-success-50 dark:border-success-500/20 dark:bg-success-500/15",
      dot: "bg-success-500",
    },
    danger: {
      bg: "border border-error-100 bg-error-50 dark:border-error-500/20 dark:bg-error-500/15",
      dot: "bg-error-500",
    },
    primary: {
      bg: "border border-brand-100 bg-brand-50 dark:border-brand-500/20 dark:bg-brand-500/15",
      dot: "bg-brand-500",
    },
    warning: {
      bg: "border border-warning-100 bg-warning-50 dark:border-warning-500/20 dark:bg-warning-500/15",
      dot: "bg-warning-500",
    },
    // Supplier documents from Infor
    labels: {
      bg: "border border-success-200 bg-success-50 hover:bg-success-100 dark:border-success-500/30 dark:bg-success-500/15 dark:hover:bg-success-500/25",
      dot: "bg-success-500",
    },
    di: {
      bg: "border border-blue-light-200 bg-blue-light-50 hover:bg-blue-light-100 dark:border-blue-light-500/30 dark:bg-blue-light-500/15 dark:hover:bg-blue-light-500/25",
      dot: "bg-blue-light-500",
    },
  };

  // Supplier documents per day ("2026-10-05" -> { labels, di }), filled by
  // the documents event source and read when a day is clicked.
  const documentsUrl = calendarEl.dataset.documentsUrl;
  let documentsByDay = {};

  function documentEvents(days) {
    const events = [];
    for (const [date, info] of Object.entries(days)) {
      if (info.labels > 0) {
        events.push({
          id: `labels-${date}`,
          title: "Etiquetas",
          start: date,
          allDay: true,
          extendedProps: { type: "labels", document: true },
        });
      }
      if (info.di > 0) {
        events.push({
          id: `di-${date}`,
          title: "Instrucción de entrega",
          start: date,
          allDay: true,
          extendedProps: { type: "di", document: true },
        });
      }
    }
    return events;
  }

  // Event source: asks the server which days have labels or DI in the visible range
  async function fetchDocuments(info) {
    if (!documentsUrl) return [];
    const params = new URLSearchParams({
      start: info.startStr.slice(0, 10),
      end: info.endStr.slice(0, 10),
    });
    try {
      const response = await fetch(`${documentsUrl}?${params}`, {
        headers: { Accept: "application/json" },
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const days = await response.json();
      documentsByDay = { ...documentsByDay, ...days };
      calendarEl.dispatchEvent(new CustomEvent("documents-status", { detail: { ok: true }, bubbles: true }));
      return documentEvents(days);
    } catch (error) {
      console.error("Could not load supplier documents", error);
      calendarEl.dispatchEvent(new CustomEvent("documents-status", { detail: { ok: false }, bubbles: true }));
      return [];
    }
  }

  // Opens the day modal (calender-area.blade.php listens for this event)
  function openDay(dateStr) {
    const date = dateStr.slice(0, 10);
    const info = documentsByDay[date] || { labels: 0, di: 0 };
    window.dispatchEvent(new CustomEvent("calendar-day", { detail: { date, ...info } }));
  }

  // Events come from the server as JSON in data-events:
  // [{ "start": "2026-09-01", "end": "2026-09-05", "title": "Laboral", "type": "success" }]
  // By default they render as a label (display: "block"); pass
  // "display": "background" to shade the whole day instead (e.g. weekends).
  function parseEvents() {
    let raw = [];
    try {
      raw = JSON.parse(calendarEl.dataset.events || "[]");
    } catch (e) {
      console.error("Invalid calendar events JSON", e);
    }

    return raw.map((event, index) => {
      const type = String(event.type || "success").toLowerCase();
      const display = event.display || "block";
      return {
        id: String(event.id ?? index),
        title: event.title || "",
        start: event.start,
        end: event.end,
        allDay: true,
        display,
        className:
          display === "background"
            ? BG_EVENT_CLASSES[type] || BG_EVENT_CLASSES.success
            : "",
        extendedProps: { type },
      };
    });
  }

  // "septiembre 2026" instead of the locale default "septiembre de 2026"
  const monthNameFormatter = new Intl.DateTimeFormat("es", { month: "long" });
  const formatMonthYear = ({ date }) =>
    `${monthNameFormatter.format(new Date(date.year, date.month, 1))} ${date.year}`;

  const bookmarkIcon = `<span><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 sm:size-5.5 text-brand-500"><path d="M19 3v17a1 1 0 01-1.496.868l-4.512-2.578a2 2 0 00-1.984 0l-4.512 2.578A1 1 0 015 20V3z" /></svg></span>`;

  // Today: brand ring + bold number, no background so marked-day colors stay visible
  const todayCellClass =
    "z-1! ring-2! ring-inset! ring-brand-500! dark:ring-brand-400!";
  const todayNumberClass = "font-bold! text-brand-600! dark:text-brand-400!";

  const popoverHeaderClass =
    "relative! border-b! border-gray-200! bg-gray-50/70! px-4! py-3! text-start! dark:border-gray-800! dark:bg-gray-800/50!";

  function buildViews() {
    return {
      dayGridMonth: {
        titleFormat: formatMonthYear,
        tableClass: "overflow-hidden",
        dayMaxEvents: isMobile ? 1 : 2,
        dayHeaderAlign: (data) => (data.inPopover ? "start" : "center"),
        dayHeaderClass: (data) =>
          data.inPopover
            ? popoverHeaderClass
            : "border-x-0! border-t border-gray-200! bg-gray-50 dark:border-gray-800! dark:bg-gray-900",
        dayHeaderInnerClass: (data) =>
          data.inPopover
            ? "text-sm! font-semibold! text-gray-800! dark:text-white/90!"
            : "px-1! py-2! sm:px-3! sm:py-3! md:px-5! md:py-4! text-xs! sm:text-sm! font-medium! text-gray-400 uppercase",
        dayCellClass: (data) => {
          if (data.inPopover) return "bg-transparent! p-3!";
          return `cursor-pointer bg-transparent! p-1! sm:p-2! ${data.isToday ? todayCellClass : ""}`;
        },
        dayCellInnerClass: (data) => {
          if (data.inPopover)
            return "flex custom-scrollbar max-h-60 flex-col gap-1.5 overflow-y-auto";
          return "";
        },
        dayCellTopInnerClass: (data) =>
          data.isToday ? todayNumberClass : "text-gray-700 dark:text-gray-300",
        moreLinkClass:
          "border-0! bg-transparent! p-0! hover:bg-transparent! focus:outline-none",
        moreLinkContent: (args) => {
          if (isMobile) return { html: bookmarkIcon };
          return {
            html: `<span class="fc-more-link-badge inline-flex items-center rounded-sm bg-brand-50 px-1 py-0.5 sm:px-1.5 text-[10px] sm:text-xs font-medium text-brand-600 transition-colors hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 dark:hover:bg-brand-500/25">+${args.num} más</span>`,
          };
        },
      },

    };
  }

  // The month fills the remaining viewport height
  function computeHeight() {
    const card = calendarEl.closest("[data-calendar-card]") || calendarEl;
    const cardTop = card.getBoundingClientRect().top + window.scrollY;
    const extras = card.offsetHeight - calendarEl.offsetHeight; // warning banner, borders
    const bottomGap = window.innerWidth >= 768 ? 24 : 16; // layout padding
    return Math.max(480, window.innerHeight - cardTop - extras - bottomGap);
  }

  const navButtonClass =
    "flex size-9! sm:size-10! p-0! items-center justify-center! rounded-lg! border! bg-transparent! border-gray-200! text-gray-700 hover:border-gray-200 hover:bg-gray-50! focus:shadow-none active:border-gray-200! active:bg-transparent! active:shadow-none! dark:border-gray-800! dark:text-gray-400 dark:hover:border-gray-800 dark:hover:bg-gray-900! dark:active:border-gray-800!";

  const calendar = new Calendar(calendarEl, {
    plugins: [themePlugin, dayGridPlugin, interactionPlugin],
    locales: [esLocale],
    locale: "es",
    initialView: "dayGridMonth",
    direction: isRtl ? "rtl" : "ltr",
    height: computeHeight(),

    // Read-only
    editable: false,
    selectable: false,

    headerToolbar: {
      start: "prev,next today",
      center: "title",
      end: "",
    },
    headerToolbarClass:
      "sticky top-0! z-20! bg-white dark:bg-gray-900 flex-wrap! flex-row! items-center justify-between gap-3 sm:gap-4 [padding-inline:16px]! sm:[padding-inline:24px]! pt-4 sm:pt-6 pb-3 sm:pb-4",
    toolbarTitleClass:
      "text-base! sm:text-lg! font-semibold! capitalize text-gray-800 dark:text-white/90",
    toolbarSectionClass: (info) => {
      if (info.name === "start") {
        return "ta-toolbar-section ta-toolbar-start order-2 flex items-center justify-start sm:order-1 gap-2";
      }
      if (info.name === "center") {
        return "ta-toolbar-section ta-toolbar-center order-1 flex w-full items-center justify-start sm:order-2 sm:w-auto sm:justify-center";
      }
      if (info.name === "end") {
        return "ta-toolbar-section ta-toolbar-end order-3 flex items-center justify-end";
      }
      return "ta-toolbar-section";
    },
    buttonGroupClass: "gap-2",
    buttons: {
      prev: {
        iconContent: {
          html: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-5 sm:size-6 bg-transparent text-gray-700 rtl:rotate-180 dark:text-gray-400"><path d="M15 18l-6-6 6-6" /></svg>`,
        },
        hint: "Mes anterior",
        className: navButtonClass,
      },
      next: {
        iconContent: {
          html: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="size-5 sm:size-6 bg-transparent text-gray-700 rtl:rotate-180 dark:text-gray-400"><path d="M9 18l6-6-6-6" /></svg>`,
        },
        hint: "Mes siguiente",
        className: navButtonClass,
      },
      today: {
        text: "Hoy",
        hint: "Ir a hoy",
        className:
          "h-9! sm:h-10! rounded-lg! border! border-gray-200! bg-transparent! px-3! sm:px-4! text-xs! sm:text-sm! font-medium! text-gray-700! hover:bg-gray-50! focus:shadow-none! disabled:opacity-50! dark:border-gray-800! dark:text-gray-400! dark:hover:bg-gray-900!",
      },
    },

    views: buildViews(),

    borderless: true,
    viewClass:
      "border-t! border-b-0! border-x-0! border-gray-200! bg-transparent! dark:border-gray-800! dark:bg-transparent!",
    dayHeaderDividerClass:
      "border-b! border-t-0! border-x-0! border-gray-200! p-0! bg-transparent! dark:border-gray-800!",
    tableHeaderSticky: true,
    tableClass: "bg-transparent!",
    eventClass: "focus:shadow-none cursor-pointer",
    rowEventClass:
      "bg-transparent! border-0! px-1! py-0.5! shadow-none! hover:shadow-none! focus:outline-none",
    rowEventInnerClass: "w-full p-0! border-0! bg-transparent!",
    backgroundEventTitleClass:
      "hidden! sm:block! text-[10px]! sm:text-xs! font-medium! not-italic! opacity-100!",
    popoverFormat: { month: "short", day: "numeric", year: "numeric" },
    popoverClass:
      "z-99999! w-72 max-w-[calc(100vw-32px)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900",
    popoverCloseClass:
      "absolute end-3 top-2.5 flex size-7 cursor-pointer items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white",
    popoverCloseContent: {
      html: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M18 6L6 18M6 6l12 12" /></svg>`,
    },

    eventSources: [{ events: parseEvents() }, { events: fetchDocuments }],
    dateClick(info) {
      openDay(info.dateStr);
    },
    eventClick(info) {
      info.jsEvent.preventDefault();
      openDay(info.event.startStr);
    },
    eventContent(eventInfo) {
      const type = eventInfo.event.extendedProps?.type || "primary";
      const colors = EVENT_COLOR_CLASSES[type] || EVENT_COLOR_CLASSES.primary;

      // Phones: no room for text, each label becomes a solid bar in its color
      if (isMobile) {
        return {
          html: `<div class="mt-1 h-1.5 w-full rounded-full ${colors.dot}"></div>`,
        };
      }

      return {
        html: `
          <div class="flex items-center rounded-md py-1 ps-1.5 pe-2 sm:rounded-lg sm:py-1.5 sm:ps-2.5 sm:pe-3 ${colors.bg}">
            <div class="ms-0 me-1 h-2.5 w-1 shrink-0 rounded-full sm:me-2 sm:h-3.5 ${colors.dot}"></div>
            <div class="fc-event-title truncate p-0 text-[11px] font-medium text-gray-700 sm:text-xs dark:text-white">${eventInfo.event.title || ""}</div>
          </div>
        `,
      };
    },
  });

  calendar.render();
  // Re-measure once the toolbar is laid out
  requestAnimationFrame(() => calendar.setOption("height", computeHeight()));

  let resizeFrame = null;
  window.addEventListener("resize", () => {
    cancelAnimationFrame(resizeFrame);
    resizeFrame = requestAnimationFrame(() => {
      const mobile = window.innerWidth < 640;
      if (isMobile !== mobile) {
        isMobile = mobile;
        calendar.setOption("views", buildViews());
      }
      calendar.setOption("height", computeHeight());
    });
  });
}
