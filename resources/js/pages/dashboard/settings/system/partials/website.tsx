import { Form, useForm, usePage } from '@inertiajs/react';
import Combobox from '@/components/combobox';
import InputError from '@/components/input-error';
import LoadingButton from '@/components/loading-button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
   Select,
   SelectContent,
   SelectItem,
   SelectTrigger,
   SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import currencies from '@/data/currencies';
import { update } from '@/routes/system';

const Website = () => {
   const { props } = usePage<SystemSettingsProps>();
   const { translate } = props;
   const { input, settings } = translate;
   const fields = props.system.fields as SystemFields;

   const { data, setData } = useForm({
      selling_currency: fields.selling_currency ?? 'USD',
   });

   return (
      <Card className="p-4 sm:p-6">
         <Form
            {...update.form(props.system.id)}
            transform={(formData) => ({
               ...fields,
               ...formData,
               ...data,
               language_selector: formData.language_selector === '1',
            })}
            className="space-y-6"
         >
            {({ processing, errors }) => (
               <>
                  {/* Website Information */}
                  <div className="border-b pb-6">
                     <h2 className="mb-4 text-xl font-semibold">
                        {settings.website_information}
                     </h2>

                     <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                           <Label>{input.website_name}</Label>
                           <Input
                              name="name"
                              defaultValue={fields.name || ''}
                              placeholder={input.website_name_placeholder}
                           />
                           <InputError message={errors.name} />
                        </div>

                        <div>
                           <Label>{input.website_title}</Label>
                           <Input
                              name="title"
                              defaultValue={fields.title || ''}
                              placeholder={input.website_title_placeholder}
                           />
                           <InputError message={errors.title} />
                        </div>

                        <div className="md:col-span-2">
                           <Label>{input.keywords}</Label>
                           <Input
                              name="keywords"
                              defaultValue={fields.keywords || ''}
                              placeholder={input.keywords_placeholder}
                           />
                           <InputError message={errors.keywords} />
                        </div>

                        <div className="md:col-span-2">
                           <Label>{input.description}</Label>
                           <Textarea
                              rows={4}
                              name="description"
                              defaultValue={fields.description || ''}
                              placeholder={input.description_placeholder}
                           />
                           <InputError message={errors.description} />
                        </div>

                        <div>
                           <Label>{input.author}</Label>
                           <Input
                              name="author"
                              defaultValue={fields.author || ''}
                              placeholder={input.author_name_placeholder}
                           />
                           <InputError message={errors.author} />
                        </div>

                        <div>
                           <Label>{input.slogan}</Label>
                           <Input
                              name="slogan"
                              defaultValue={fields.slogan || ''}
                              placeholder={input.slogan}
                           />
                           <InputError message={errors.slogan} />
                        </div>
                     </div>
                  </div>

                  {/* Contact Information */}
                  <div className="border-b pb-6">
                     <h2 className="mb-4 text-xl font-semibold">
                        Contact Information
                     </h2>

                     <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                           <Label>System Email *</Label>
                           <Input
                              type="email"
                              name="email"
                              defaultValue={fields.email || ''}
                              placeholder="Enter System Email"
                           />
                           <InputError message={errors.email} />
                        </div>

                        <div>
                           <Label>Phone</Label>
                           <Input
                              name="phone"
                              defaultValue={fields.phone || ''}
                              placeholder="Enter Phone Number"
                           />
                           <InputError message={errors.phone} />
                        </div>
                     </div>
                  </div>

                  {/* Media Settings */}
                  <div className="border-b pb-6">
                     <h2 className="mb-4 text-xl font-semibold">Media</h2>

                     <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                           <Label>Logo Dark</Label>
                           <Input
                              type="file"
                              name="new_logo_dark"
                              accept="image/*"
                              placeholder="Select Logo"
                           />
                           <InputError message={errors.new_logo_dark} />
                        </div>

                        <div>
                           <Label>Logo Light</Label>
                           <Input
                              type="file"
                              name="new_logo_light"
                              accept="image/*"
                              placeholder="Select Logo"
                           />
                           <InputError message={errors.new_logo_light} />
                        </div>

                        <div>
                           <Label>Favicon</Label>
                           <Input
                              type="file"
                              name="new_favicon"
                              accept="image/*"
                              placeholder="Select Favicon"
                           />
                           <InputError message={errors.new_favicon} />
                        </div>

                        <div>
                           <Label>Banner</Label>
                           <Input
                              type="file"
                              name="new_banner"
                              accept="image/*"
                              placeholder="Select Banner"
                           />
                           <InputError message={errors.new_banner} />
                        </div>

                        <div>
                           <Label>
                              Auth Banner{' '}
                              <span className="text-xs text-muted-foreground">
                                 (For Login, Register, etc pages)
                              </span>
                           </Label>
                           <Input
                              type="file"
                              name="new_auth_banner"
                              accept="image/*"
                              placeholder="Select Auth Banner"
                           />
                           <InputError message={errors.new_auth_banner} />
                        </div>
                     </div>
                  </div>

                  <div>
                     <h2 className="mb-4 text-xl font-semibold">
                        Additional Settings
                     </h2>

                     <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                           <Label>{'Website Direction'}</Label>
                           <Select
                              name="direction"
                              defaultValue={fields.direction ?? 'none'}
                           >
                              <SelectTrigger>
                                 <SelectValue
                                    placeholder={input.select_option}
                                 />
                              </SelectTrigger>
                              <SelectContent>
                                 <SelectItem value="none">None</SelectItem>
                                 <SelectItem value="ltr">LTR</SelectItem>
                                 <SelectItem value="rtl">RTL</SelectItem>
                              </SelectContent>
                           </Select>
                           <InputError message={errors.direction} />
                        </div>

                        <div>
                           <Label>{'Default Theme'}</Label>
                           <Select
                              name="theme"
                              defaultValue={fields.theme ?? 'system'}
                           >
                              <SelectTrigger>
                                 <SelectValue
                                    placeholder={input.select_option}
                                 />
                              </SelectTrigger>
                              <SelectContent>
                                 <SelectItem value="system">System</SelectItem>
                                 <SelectItem value="light">Light</SelectItem>
                                 <SelectItem value="dark">Dark</SelectItem>
                              </SelectContent>
                           </Select>
                           <InputError message={errors.theme} />
                        </div>

                        <div>
                           <Label>{'Language Selector'}</Label>
                           <Select
                              name="language_selector"
                              defaultValue={
                                 fields.language_selector ? '1' : '0'
                              }
                           >
                              <SelectTrigger>
                                 <SelectValue
                                    placeholder={input.select_option}
                                 />
                              </SelectTrigger>
                              <SelectContent>
                                 <SelectItem value="1">Show</SelectItem>
                                 <SelectItem value="0">Hide</SelectItem>
                              </SelectContent>
                           </Select>
                           <InputError message={errors.language_selector} />
                        </div>

                        <div>
                           <Label>{`Course Selling Currency (${data.selling_currency})`}</Label>
                           <Combobox
                              data={currencies}
                              defaultValue={data.selling_currency}
                              placeholder="Select a selling currency"
                              onSelect={(selected) =>
                                 setData('selling_currency', selected.value)
                              }
                           />
                           <InputError message={errors.selling_currency} />
                        </div>

                        <div>
                           <Label>{'Course Selling Tax (%)'}</Label>
                           <Input
                              name="selling_tax"
                              defaultValue={fields.selling_tax || ''}
                              placeholder="Enter Course Selling Tax Percentage"
                           />
                           <InputError message={errors.selling_tax} />
                        </div>

                        {/* Other Settings */}
                        {props.system.sub_type === 'collaborative' && (
                           <div>
                              <Label>{'Instructor Revenue (%)'}</Label>
                              <Input
                                 name="instructor_revenue"
                                 defaultValue={fields.instructor_revenue || ''}
                                 placeholder="Enter Instructor Revenue Percentage"
                              />
                              <InputError message={errors.instructor_revenue} />
                           </div>
                        )}
                     </div>
                  </div>

                  <LoadingButton loading={processing} className="float-end">
                     Save Changes
                  </LoadingButton>
               </>
            )}
         </Form>
      </Card>
   );
};

export default Website;
