# Dev Dependencies

npm install -D @types/react-google-recaptcha @types/yaireo\_\_tagify

# Dependencies

npm install @codemirror/autocomplete @codemirror/closebrackets @codemirror/lang-css @codemirror/lang-html @codemirror/lang-json @codemirror/lint @codemirror/matchbrackets @dnd-kit/core @dnd-kit/sortable @dnd-kit/utilities @radix-ui/react-switch @radix-ui/react-tabs @tanstack/react-table @zoom/meetingsdk codemirror embla-carousel-autoplay jspdf lucide-static nanoid plyr-react react-google-recaptcha

Go to the @Modules/AIAssistant/ directory and thoroughly analyze the entire module to understand its architecture, features, workflows, configuration, and access control mechanisms.

I need a new feature called **Instructor Restriction**.

Requirements:

1. Add an option on the AIAssistant configuration page to select one or more instructors. Here is the current configuration page @Modules/AIAssistant/resources/js/pages/configuration.tsx
2. Any instructor selected in this restriction list must not be able to access or use any AI Assistant features throughout the application.
3. Restricted instructors should not see AI Assistant buttons or related UI elements. In this case you don't need to apply condition to the all component where it is used. because I have used usePlugin('AIAssistant') hook to check if the plugin is enabled and show the buttons. Here is the reference file @resources/js/hooks/use-plugin.tsx . so if you apply the condition inside this component then it will automatically apply globally.
4. They should also be blocked from accessing AI Assistant functionality through direct URLs or API requests.
5. Follow the existing coding standards and architecture of the module.

Please review the complete module first and then proceed with the implementation.
