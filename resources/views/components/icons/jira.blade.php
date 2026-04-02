@props(['class' => 'size-5'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M11.53 2C11.53 2 4.24 8.3 4.24 12.18C4.24 14.89 6.05 17.14 8.52 17.93L11.53 14.92V2Z" fill="#2684FF"/>
    <path d="M12.47 2V14.92L15.48 17.93C17.95 17.14 19.76 14.89 19.76 12.18C19.76 8.3 12.47 2 12.47 2Z" fill="url(#jira-gradient)"/>
    <path d="M8.52 17.93C8.52 17.93 10.14 19.54 12 19.54C13.86 19.54 15.48 17.93 15.48 17.93L12 21.41L8.52 17.93Z" fill="url(#jira-gradient-2)"/>
    <defs>
        <linearGradient id="jira-gradient" x1="12.47" y1="14.92" x2="19.76" y2="12.18"><stop stop-color="#0052CC"/><stop offset="1" stop-color="#2684FF"/></linearGradient>
        <linearGradient id="jira-gradient-2" x1="8.52" y1="17.93" x2="15.48" y2="17.93"><stop stop-color="#2684FF"/><stop offset="1" stop-color="#0052CC"/></linearGradient>
    </defs>
</svg>
