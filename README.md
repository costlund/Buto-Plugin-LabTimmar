# Buto-Plugin-LabTimmar
Plugin to handle data from Google Calendar like a project with time summary and progress support. 

## How it works

### Setup
- Create a Google calendar on calendar.google.com.
- In GUI add a new calendar registration with a name and filename where filename is your Google calendar secret address.

### Usage
- Registrations for hole days are Project.
- Registrations hourly is Time registration.
- Project can have any name.
- Time must have same name as Project.
- Project and Time can have extra content but always after a comma.
- Add params hours_approx and work_percentage(only for display) in Project for progress support.


## Theme settings

```
plugin_modules:
  timmar:
    plugin: lab/timmar
    settings:
      mysql: 'yml:/../buto_data/xxx/yyy/mysql.yml'
      admin_layout: /theme/xxx/yyy/layout/application.yml
```

```
plugin:
  lab:
    timmar:
      enabled: true
```


Schema file.
```
/plugin/lab/timmar/mysql/schema.yml
```

Param in description
```
hours_approx: 4
work_percentage: 75
```


