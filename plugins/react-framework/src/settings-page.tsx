import React, { useState, useEffect } from "react";
import { __ } from "@wordpress/i18n";
import { Button as WordpressButton, TabPanel } from "@wordpress/components";
import apiFetch from "@wordpress/api-fetch";
import { Check, ChevronsUpDown } from "lucide-react";

import type { WP_REST_API_Page } from "wp-types";

// Import Tailwind styles
import "./style.css";
import { Button as ShadcnButton } from "~/components/ui/button";
import DataTableDemo from "~/components/table/order-table";

import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableFooter,
  TableHead,
  TableHeader,
  TableRow,
} from "~/components/ui/table";

import { cn } from "~/lib/utils";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "~/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "~/components/ui/popover";

const SettingsPage = () => {
  const [generalOption, setGeneralOption] = useState("");
  const [notificationOption, setNotificationOption] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [initialTab, setInitialTab] = useState("");
  const [pages, setPages] = useState<WP_REST_API_Page[]>([]);
  const [value, setValue] = React.useState("");

  const [feature1, setFeature1] = useState<{ page_id: number | null }>({
    page_id: null,
  });

  useEffect(() => {
    apiFetch({ path: "/wp/v2/settings" }).then((settings) => {
      console.log(settings.unadorned_announcement_bar.feature_1.page_id);
      setFeature1({
        page_id: settings.unadorned_announcement_bar.feature_1.page_id,
      });
      setValue(
        settings.unadorned_announcement_bar.feature_1.page_id.toString()
      );
    });
  }, []);

  useEffect(() => {
    console.log("SettingsPage mounted");
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get("tab");
    const pageParam = params.get("page");
    if (
      pageParam === "unadorned-announcement-bar" &&
      tabParam &&
      ["general", "notifications", "api"].includes(tabParam)
    ) {
      setInitialTab(tabParam);
    } else {
      setInitialTab("general");
    }

    // Fetch WordPress pages
    apiFetch({ path: "/wp/v2/pages" }).then(
      (fetchedPages: WP_REST_API_Page[]) => {
        setPages(fetchedPages);
      }
    );
  }, []);

  useEffect(() => {
    if (pages.length > 0) {
      console.log("Updated pages:", pages);
    }
  }, [pages]);

  const handleTabChange = (tabName) => {
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set("page", "unadorned-announcement-bar");
    searchParams.set("tab", tabName);
    const newUrl = `${window.location.pathname}?${searchParams.toString()}`;
    window.history.pushState({ path: newUrl }, "", newUrl);
  };

  const handleSave = () => {
    try {
      apiFetch({
        path: "/wp/v2/settings",
        method: "POST",
        data: {
          unadorned_announcement_bar: {
            feature_1: {
              page_id: value, // Ensure you're using feature_1 as in the original structure
            },
          },
        },
      })
        .then((response) => {
          console.log("Settings saved successfully:", response);
        })
        .catch((error) => {
          console.error("Error saving settings:", error);
        });
    } catch (error) {
      console.error("Caught error during save:", error);
    }
  };

  const features = [
    {
      name: "Feature 1",
    },
  ];

  const NotificationsContent = () => {
    const [openComboboxes, setOpenComboboxes] = useState({});
    const [selectedValues, setSelectedValues] = useState({});

    const [open, setOpen] = React.useState(false);

    const handleComboboxOpenChange = (featureName, isOpen) => {
      setOpenComboboxes((prev) => ({ ...prev, [featureName]: isOpen }));
    };

    const handleComboboxSelect = (featureName, currentValue) => {
      setSelectedValues((prev) => ({
        ...prev,
        [featureName]:
          currentValue === selectedValues[featureName] ? "" : currentValue,
      }));
      handleComboboxOpenChange(featureName, false);
    };

    const frameworks = [
      {
        value: "next.js",
        label: "Next.js",
      },
      {
        value: "sveltekit",
        label: "SvelteKit",
      },
      {
        value: "nuxt.js",
        label: "Nuxt.js",
      },
      {
        value: "remix",
        label: "Remix",
      },
      {
        value: "astro",
        label: "Astro",
      },
    ];

    return (
      <div>
        <Table className="w-[400px]">
          <TableCaption>A list of the plugin features.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead className="w-[100px]">Feature</TableHead>
              <TableHead>Wordpress Page</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {features.map((feature) => (
              <TableRow key={feature.name}>
                <TableCell className="font-medium">{feature.name}</TableCell>
                <TableCell>
                  <Popover
                    open={openComboboxes[feature.name]}
                    onOpenChange={(isOpen) =>
                      handleComboboxOpenChange(feature.name, isOpen)
                    }
                  >
                    <PopoverTrigger asChild>
                      <ShadcnButton
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        className="w-[200px] justify-between"
                      >
                        {value
                          ? pages.find((page) => page.id.toString() === value)
                              ?.title.rendered
                          : "Select page..."}
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                      </ShadcnButton>
                    </PopoverTrigger>
                    <PopoverContent className="w-[200px] p-0">
                      <Command>
                        <CommandInput placeholder="Search pages..." />
                        <CommandList>
                          <CommandEmpty>No page found.</CommandEmpty>
                          <CommandGroup>
                            {pages.map((page) => (
                              <CommandItem
                                key={page.id}
                                value={page.id.toString()}
                                onSelect={(currentValue) => {
                                  setValue(
                                    currentValue === value ? "" : currentValue
                                  );
                                  setOpen(false);
                                }}
                              >
                                <Check
                                  className={cn(
                                    "mr-2 h-4 w-4",
                                    value === page.id.toString()
                                      ? "opacity-100"
                                      : "opacity-0"
                                  )}
                                />
                                {page.title.rendered}
                                <span className="text-[.6rem]">
                                  ( id: <strong>{page.id}</strong> )
                                </span>
                              </CommandItem>
                            ))}
                          </CommandGroup>
                        </CommandList>
                      </Command>
                    </PopoverContent>
                  </Popover>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    );
  };

  const renderTabContent = (tab: { name: any }) => {
    switch (tab.name) {
      case "general":
        return <DataTableDemo />;
      case "notifications":
        return <NotificationsContent />;
      case "api":
        return <div>Content for Tab 3</div>;
      default:
        return null;
    }
  };

  return (
    <div className="brads-boilerplate-settings">
      <h1>{__("React Admin Area", "brads-boilerplate")}</h1>
      <div className="flex justify-between items-start mb-4">
        {initialTab &&
          (console.log("initialTab", initialTab),
          (
            <TabPanel
              className="my-tab-panel text-black flex-grow"
              activeClass="active-tab bg-slate-300 !text-black"
              tabs={[
                {
                  name: "general",
                  title: "General Settings",
                  className: "tab-general",
                },
                {
                  name: "notifications",
                  title: "Notifications",
                  className: "tab-notifications",
                },
                {
                  name: "api",
                  title: "API",
                  className: "tab-api",
                },
              ]}
              onSelect={handleTabChange}
              initialTabName={initialTab && initialTab}
            >
              {renderTabContent}
            </TabPanel>
          ))}
        <WordpressButton isPrimary onClick={handleSave} className="ml-4">
          {__("Save All Settings", "brads-boilerplate")}
        </WordpressButton>
        <ShadcnButton>Save</ShadcnButton>
      </div>
    </div>
  );
};

export default SettingsPage;
